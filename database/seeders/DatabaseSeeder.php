<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed initial demo data for StartWork based on PRD.md, Database.md, and Architecture.md.
     */
    public function run(): void
    {
        // ==========================================
        // 1. SEED PACKAGES (PRD 3.7)
        // ==========================================
        $packages = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Basic',
                'quota' => 10,
                'duration_days' => 15,
                'price' => 99000.00,
                'currency' => 'IDR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Standard',
                'quota' => 20,
                'duration_days' => 30,
                'price' => 149000.00,
                'currency' => 'IDR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Premium',
                'quota' => 30,
                'duration_days' => 45,
                'price' => 239000.00,
                'currency' => 'IDR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($packages as $pkg) {
            DB::table('packages')->updateOrInsert(
                ['name' => $pkg['name']],
                $pkg
            );
        }

        // ==========================================
        // 2. SEED USERS 3 ROLES (PRD 2)
        // ==========================================

        // A. Admin Internal
        $admin = DB::table('users')->where('email', 'admin@startwork.test')->first();
        if (! $admin) {
            $adminId = (string) Str::uuid();
            DB::table('users')->insert([
                'id' => $adminId,
                'full_name' => 'Internal Admin StartWork',
                'email' => 'admin@startwork.test',
                'password_hash' => Hash::make('password'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $adminId = $admin->id;
            DB::table('users')->where('id', $adminId)->update([
                'password_hash' => Hash::make('password'),
            ]);
        }

        // B. Employer
        $employer = DB::table('users')->where('email', 'demo-employer@startwork.test')->first();
        if (! $employer) {
            $employerId = (string) Str::uuid();
            DB::table('users')->insert([
                'id' => $employerId,
                'full_name' => 'Demo Employer',
                'email' => 'demo-employer@startwork.test',
                'password_hash' => Hash::make('password'),
                'role' => 'employer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $employerId = $employer->id;
            DB::table('users')->where('id', $employerId)->update([
                'password_hash' => Hash::make('password'),
            ]);
        }

        // Company untuk Employer
        $company = DB::table('companies')->where('owner_user_id', $employerId)->first();
        if (! $company) {
            $companyId = (string) Str::uuid();
            DB::table('companies')->insert([
                'id' => $companyId,
                'owner_user_id' => $employerId,
                'created_by' => $employerId,
                'name' => 'Startwork Demo Company',
                'location' => 'Jakarta',
                'website' => 'https://startwork.test',
                'description' => 'Perusahaan contoh untuk pengujian endpoint StartWork.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $companyId = $company->id;
        }

        // C. Jobseeker
        $jobseeker = DB::table('users')->where('email', 'demo-jobseeker@startwork.test')->first();
        if (! $jobseeker) {
            $jobseekerId = (string) Str::uuid();
            DB::table('users')->insert([
                'id' => $jobseekerId,
                'full_name' => 'Demo Jobseeker',
                'email' => 'demo-jobseeker@startwork.test',
                'password_hash' => Hash::make('password'),
                'role' => 'jobseeker',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $jobseekerId = $jobseeker->id;
            DB::table('users')->where('id', $jobseekerId)->update([
                'password_hash' => Hash::make('password'),
            ]);
        }

        // Profile & CV untuk Jobseeker (PRD 3.5 & 3.6)
        $profile = DB::table('jobseeker_profiles')->where('user_id', $jobseekerId)->first();
        if (! $profile) {
            $profileId = (string) Str::uuid();
            DB::table('jobseeker_profiles')->insert([
                'id' => $profileId,
                'user_id' => $jobseekerId,
                'full_name' => 'Demo Jobseeker',
                'domicile' => 'Jakarta Selatan',
                'birth_date' => '2001-08-20',
                'gender' => 'Laki-laki',
                'last_education' => 'S1 Teknik Informatika',
                'portfolio_link' => 'https://github.com/demo-jobseeker',
                'cv_file' => 'storage/cv/demo-jobseeker-cv.pdf',
                'cv_filename' => 'demo-jobseeker-cv.pdf',
                'cv_uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 3. SEED ARTIKEL (PRD 3.3 & 4.3)
        // ==========================================
        if (DB::table('articles')->count() === 0) {
            DB::table('articles')->insert([
                'id' => (string) Str::uuid(),
                'author_id' => $adminId,
                'title' => 'Panduan Sukses Lolos Interview Kerja di Era Digital',
                'cover_image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2',
                'content' => 'Berikut adalah tips praktis mempersiapkan diri menghadapi interview kerja baik secara online maupun offline...',
                'status' => 'published',
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
