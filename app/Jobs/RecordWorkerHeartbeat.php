<?php

namespace App\Jobs;

use App\Models\OperationalHeartbeat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class RecordWorkerHeartbeat implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        OperationalHeartbeat::updateOrCreate(
            ['process' => 'worker'],
            [
                'instance_id' => (string) Str::uuid(),
                'last_seen_at' => now(),
                'metadata' => ['queue' => $this->queue ?: 'default'],
            ]
        );
    }
}
