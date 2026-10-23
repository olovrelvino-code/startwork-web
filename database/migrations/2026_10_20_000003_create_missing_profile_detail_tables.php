<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'work_experiences' => [
                'job_type' => 'string', 'job_title' => 'string', 'company' => 'string',
                'start_date' => 'date', 'end_date' => 'date', 'key_responsibilities' => 'text',
            ],
            'organization_experiences' => [
                'organization_name' => 'string', 'role' => 'string',
                'start_date' => 'date', 'end_date' => 'date', 'description' => 'text',
            ],
            'education_histories' => [
                'level' => 'string', 'institution' => 'string', 'major' => 'string',
                'start_date' => 'date', 'end_date' => 'date',
            ],
            'skills' => ['name' => 'string'],
            'certificates' => ['name' => 'string', 'file_url' => 'string'],
        ];

        foreach ($columns as $tableName => $fields) {
            if (Schema::hasTable($tableName)) {
                continue;
            }

            Schema::create($tableName, function (Blueprint $table) use ($tableName, $fields): void {
                $table->uuid('id')->primary();
                $table->uuid('profile_id');
                foreach ($fields as $name => $type) {
                    $table->{$type}($name)->nullable();
                }
                $table->timestamps();
                $table->index('profile_id', "idx_{$tableName}_profile");
                $table->foreign('profile_id')->references('id')->on('jobseeker_profiles');
            });
        }
    }

    public function down(): void
    {
        // Existing installations may have created these tables manually.
        // Do not drop user data during rollback.
    }
};
