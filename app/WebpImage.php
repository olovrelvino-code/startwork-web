<?php

namespace App;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WebpImage
{
    /** @return array<int, string> */
    public static function rules(): array
    {
        return ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'];
    }

    public static function store(
        UploadedFile $file,
        string $directory,
        string $field,
        string $disk = 'public',
        ?int $maxDimension = null,
        ?int $maxBytes = null
    ): string {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Konversi WebP tidak tersedia pada PHP server.');
        }

        $source = $file->getRealPath();
        $details = $source ? @getimagesize($source) : false;
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if (defined('IMAGETYPE_WEBP')) {
            $allowedTypes[] = IMAGETYPE_WEBP;
        }

        if (! $details || ! in_array($details[2], $allowedTypes, true)
            || $details[0] < 1 || $details[1] < 1
            || $details[0] > 5000 || $details[1] > 5000
            || $details[0] * $details[1] > 35000000) {
            throw ValidationException::withMessages([
                $field => 'Gambar tidak valid atau dimensinya terlalu besar.',
            ]);
        }

        $contents = @file_get_contents($source);
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        if ($image === false) {
            throw ValidationException::withMessages([$field => 'Gambar tidak dapat diproses.']);
        }

        if (! imageistruecolor($image) && ! imagepalettetotruecolor($image)) {
            imagedestroy($image);
            throw ValidationException::withMessages([$field => 'Gambar tidak dapat diproses.']);
        }
        if ($details[2] === IMAGETYPE_PNG || (defined('IMAGETYPE_WEBP') && $details[2] === IMAGETYPE_WEBP)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }
        if ($maxDimension !== null && max($details[0], $details[1]) > $maxDimension) {
            $scale = $maxDimension / max($details[0], $details[1]);
            $resized = imagescale(
                $image,
                max(1, (int) round($details[0] * $scale)),
                max(1, (int) round($details[1] * $scale)),
                IMG_BICUBIC_FIXED
            );
            imagedestroy($image);
            if ($resized === false) {
                throw ValidationException::withMessages([$field => 'Gambar tidak dapat diproses.']);
            }
            $image = $resized;
        }

        try {
            do {
                ob_start();
                try {
                    $converted = @imagewebp($image, null, 82);
                    $webp = ob_get_contents();
                } finally {
                    ob_end_clean();
                }
                if (! $converted || ! is_string($webp)) {
                    throw ValidationException::withMessages([$field => 'Konversi gambar gagal. Coba gambar lain.']);
                }
                if ($maxBytes === null || strlen($webp) <= $maxBytes) {
                    break;
                }
                $resized = imagescale($image, max(1, (int) (imagesx($image) * 0.8)), max(1, (int) (imagesy($image) * 0.8)), IMG_BICUBIC_FIXED);
                if ($resized === false) {
                    throw ValidationException::withMessages([$field => 'Gambar tidak dapat dikompres.']);
                }
                imagedestroy($image);
                $image = $resized;
            } while (imagesx($image) > 1 || imagesy($image) > 1);
        } finally {
            imagedestroy($image);
        }

        $result = is_string($webp) ? @getimagesizefromstring($webp) : false;
        if (! $result || $result['mime'] !== 'image/webp' || ($maxBytes !== null && strlen($webp) > $maxBytes)) {
            throw ValidationException::withMessages([$field => 'Konversi gambar gagal. Coba gambar lain.']);
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        try {
            if (! Storage::disk($disk)->put($path, $webp)) {
                throw new RuntimeException('Gagal menyimpan gambar WebP.');
            }
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }

        return $path;
    }
}
