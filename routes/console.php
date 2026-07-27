<?php

use App\Jobs\RecordWorkerHeartbeat;
use App\Models\OperationalHeartbeat;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Schedule::command('survey:waves:schedule')
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::call(function (): void {
    OperationalHeartbeat::updateOrCreate(
        ['process' => 'scheduler'],
        [
            'instance_id' => (string) Str::uuid(),
            'last_seen_at' => now(),
            'metadata' => ['source' => 'laravel_scheduler'],
        ]
    );
    RecordWorkerHeartbeat::dispatch();
})->name('operational-heartbeats')->everyMinute()->withoutOverlapping(3);
