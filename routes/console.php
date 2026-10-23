<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


\Illuminate\Support\Facades\Artisan::command('job-posts:expire', function (): void {
    $count = \App\JobPostLifecycle::expireDue();
    $this->info("{$count} lowongan diubah menjadi expired.");
})->purpose('Mengubah lowongan yang melewati expires_at menjadi expired');

\Illuminate\Support\Facades\Schedule::command('job-posts:expire')->everyMinute();