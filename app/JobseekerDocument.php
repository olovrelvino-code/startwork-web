<?php

namespace App;

use Closure;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class JobseekerDocument
{
    public static function disk(): FilesystemAdapter
    {
        return Storage::disk(config('filesystems.jobseeker_documents_disk'));
    }

    public static function ownedPath(?string $path, string $directory, string $userId): bool
    {
        return $path !== null && (bool) preg_match('/\A'.preg_quote($directory.'/'.$userId.'/', '/').'[0-9a-f-]{36}\.(?:pdf|jpe?g|png)\z/i', $path);
    }

    /** @return array<int, mixed> */
    public static function rules(): array
    {
        return ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile || ! $value->isValid()) {
                    return;
                }

                $extension = strtolower($value->getClientOriginalExtension());
                $path = $value->getRealPath();
                $signature = $path ? file_get_contents($path, false, null, 0, 8) : false;
                $valid = match ($extension) {
                    'pdf' => str_starts_with((string) $signature, '%PDF-'),
                    'jpg', 'jpeg' => str_starts_with((string) $signature, "\xFF\xD8\xFF"),
                    'png' => $signature === "\x89PNG\r\n\x1a\n",
                    default => false,
                };

                if (! $valid) {
                    $fail('Berkas harus berupa PDF, JPG, JPEG, atau PNG yang valid.');

                    return;
                }

                if ($extension !== 'pdf' && ($value->getSize() > 2 * 1024 * 1024 || ! in_array(@getimagesize($path)['mime'] ?? null, ['image/jpeg', 'image/png'], true))) {
                    $fail('Gambar harus valid dan berukuran maksimal 2 MB.');
                }
            },
        ];
    }
}
