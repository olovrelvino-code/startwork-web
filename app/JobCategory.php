<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobCategory
{
    public const PREDEFINED = [
        'Administration & Support',
        'Sales & Marketing',
        'Hospitality & F&B',
        'Logistics & Operations',
        'Creative & Media',
        'Retail Operations',
        'HR & People',
        'Finance & Audit',
        'Security',
        'Service',
        'Technology / IT',
        'Health & Beauty',
        'Others',
    ];

    public static function normalize(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    public static function key(string $name): string
    {
        return Str::lower(self::normalize($name));
    }

    public static function forAdmin(string $name, string $field = 'category', ?string $current = null): string
    {
        $key = self::key($name);
        foreach (self::PREDEFINED as $category) {
            if (self::key($category) === $key) {
                return $category;
            }
        }

        if ($current !== null && self::key($current) === $key) {
            return $current;
        }

        throw ValidationException::withMessages([$field => 'Pilih kategori yang tersedia.']);
    }

    public static function save(string $name): string
    {
        $name = self::normalize($name);
        $key = self::key($name);

        DB::table('job_categories')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'normalized_name' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('job_categories')->where('normalized_name', $key)->value('name');
    }
}
