
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $packages = [
            [
                'name' => 'Standard',
                'quota' => 20,
                'duration_days' => 30,
                'price' => 149000,
            ],
            [
                'name' => 'Premium',
                'quota' => 30,
                'duration_days' => 45,
                'price' => 239000,
            ],
        ];

        foreach ($packages as $package) {
            if (DB::table('packages')
                ->where('name', $package['name'])
                ->exists()) {
                continue;
            }

            DB::table('packages')->insert([
                'id' => (string) Str::uuid(),
                'name' => $package['name'],
                'quota' => $package['quota'],
                'duration_days' => $package['duration_days'],
                'price' => $package['price'],
                'currency' => 'IDR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Jangan hapus paket karena mungkin
        // sudah direferensikan oleh transaksi.
    }
};
