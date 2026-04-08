<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\RecordLockService;
use App\Services\ArchivalService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(RecordLockService::class)->releaseExpired();
})->everyThirtyMinutes()->name('release-expired-record-locks')->withoutOverlapping();

Schedule::call(function () {
    app(ArchivalService::class)->archiveResolvedRecords(90);
})->dailyAt('01:00')->name('archive-resolved-records')->withoutOverlapping();
