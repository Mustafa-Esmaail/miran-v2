<?php

use App\Console\Commands\AutoFlagUncontactedComplaintsCommand;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('permissions:sync', function () {
    $this->call(PermissionSeeder::class);
    $this->info('Permissions synced.');
})->purpose('Sync application permissions');

Schedule::command(AutoFlagUncontactedComplaintsCommand::class)->everyFifteenMinutes();
