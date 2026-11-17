<?php

use App\JobPostLifecycle;
use App\SubscriptionPurchases;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('job-posts:expire', function (): void {
    $count = JobPostLifecycle::expireDue();
    $this->info("{$count} lowongan diubah menjadi expired.");
})->purpose('Mengubah lowongan yang melewati expires_at menjadi expired');

Schedule::command('job-posts:expire')->everyMinute();

Artisan::command('subscriptions:activate-due', function (SubscriptionPurchases $purchases): void {
    $this->info($purchases->activateDue().' subscription diaktifkan.');
})->purpose('Mengaktifkan antrean subscription setelah paket aktif berakhir');

Schedule::command('subscriptions:activate-due')->everyMinute()->withoutOverlapping(5);
