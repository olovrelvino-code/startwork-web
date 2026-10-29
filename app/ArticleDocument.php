<?php

namespace App;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class ArticleDocument
{
    public static function validate(string $json, string $articleId): array
    {
        if (strlen($json) > 100000) {
            self::invalid();
        }
        $document = json_decode($json, true, 20);
        if (! is_array($document) || ! isset($document['blocks']) || ! is_array($document['blocks'])
            || ! array_is_list($document['blocks']) || count($document['blocks']) > 100) {
            self::invalid();
        }

        $blocks = [];
        foreach ($document['blocks'] as $block) {
            if (! is_array($block)) {
                self::invalid();
            }
            $type = $block['type'] ?? null;
            if (in_array($type, ['paragraph', 'heading'], true)) {
                if (! is_string($block['text'] ?? null) || strlen($block['text']) > 10000) {
                    self::invalid();
                }
                if ($type === 'heading' && ! in_array($block['level'] ?? null, [2, 3], true)) {
                    self::invalid();
                }
                $blocks[] = $type === 'heading'
                    ? ['type' => 'heading', 'level' => $block['level'], 'text' => $block['text']]
                    : ['type' => 'paragraph', 'text' => $block['text']];
            } elseif ($type === 'table') {
                $headers = $block['headers'] ?? null;
                $rows = $block['rows'] ?? null;
                if (! is_array($headers) || ! array_is_list($headers) || count($headers) < 2 || count($headers) > 8
                    || ! is_array($rows) || ! array_is_list($rows) || count($rows) > 40) {
                    self::invalid();
                }
                foreach (array_merge([$headers], $rows) as $row) {
                    if (! is_array($row) || ! array_is_list($row) || count($row) !== count($headers)) {
                        self::invalid();
                    }
                    foreach ($row as $cell) {
                        if (! is_string($cell) || strlen($cell) > 500) {
                            self::invalid();
                        }
                    }
                }
                $blocks[] = ['type' => 'table', 'headers' => $headers, 'rows' => $rows];
            } elseif ($type === 'image') {
                $file = $block['file'] ?? null;
                $alt = $block['alt'] ?? null;
                $caption = $block['caption'] ?? '';
                if (! is_string($file) || ! preg_match('/\A[0-9a-f-]{36}\.webp\z/i', $file)
                    || ! Storage::disk('local')->exists("article-media/{$articleId}/{$file}")
                    || ! is_string($alt) || trim($alt) === '' || strlen($alt) > 255
                    || ! is_string($caption) || strlen($caption) > 500) {
                    self::invalid();
                }
                $blocks[] = ['type' => 'image', 'file' => $file, 'alt' => trim($alt), 'caption' => $caption];
            } else {
                self::invalid();
            }
        }

        return $blocks;
    }

    public static function plainText(array $blocks): string
    {
        $lines = [];
        foreach ($blocks as $block) {
            if (isset($block['text'])) {
                $lines[] = $block['text'];
            } elseif ($block['type'] === 'table') {
                $lines[] = implode(' ', $block['headers']);
                foreach ($block['rows'] as $row) {
                    $lines[] = implode(' ', $row);
                }
            } elseif ($block['type'] === 'image') {
                $lines[] = $block['caption'];
            }
        }

        return trim(implode("\n", $lines));
    }

    public static function imageNames(?string $json): array
    {
        $document = $json ? json_decode($json, true, 20) : null;
        if (! is_array($document) || ! is_array($document['blocks'] ?? null)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($block) => is_array($block) && ($block['type'] ?? null) === 'image'
                && is_string($block['file'] ?? null) && preg_match('/\A[0-9a-f-]{36}\.webp\z/i', $block['file'])
                    ? $block['file'] : null,
            $document['blocks']
        )));
    }

    public static function render(?string $json, string $slug): HtmlString
    {
        $document = $json ? json_decode($json, true, 20) : null;
        $html = '';
        foreach (is_array($document['blocks'] ?? null) ? $document['blocks'] : [] as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['type'] ?? null) === 'paragraph') {
                $html .= '<p>'.nl2br(e((string) ($block['text'] ?? ''))).'</p>';
            } elseif (($block['type'] ?? null) === 'heading' && in_array($block['level'] ?? null, [2, 3], true)) {
                $level = $block['level'];
                $html .= "<h{$level}>".e((string) ($block['text'] ?? ''))."</h{$level}>";
            } elseif (($block['type'] ?? null) === 'table' && is_array($block['headers'] ?? null)
                && is_array($block['rows'] ?? null)) {
                $html .= '<div class="article-table-scroll"><table><thead><tr>';
                foreach ($block['headers'] as $cell) {
                    $html .= '<th scope="col">'.e((string) $cell).'</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($block['rows'] as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $html .= '<tr>';
                    foreach ($row as $cell) {
                        $html .= '<td>'.e((string) $cell).'</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';
            } elseif (($block['type'] ?? null) === 'image'
                && is_string($block['file'] ?? null)
                && preg_match('/\A[0-9a-f-]{36}\.webp\z/i', $block['file'])) {
                $url = route('articles.image', ['slug' => $slug, 'image' => $block['file']]);
                $html .= '<figure><img src="'.e($url).'" alt="'.e((string) ($block['alt'] ?? '')).'" loading="lazy">';
                if (! empty($block['caption'])) {
                    $html .= '<figcaption>'.e((string) $block['caption']).'</figcaption>';
                }
                $html .= '</figure>';
            }
        }

        return new HtmlString($html);
    }

    private static function invalid(): never
    {
        throw ValidationException::withMessages(['body_json' => 'Isi artikel memiliki blok yang tidak valid. Periksa kembali tabel dan gambar.']);
    }
}
