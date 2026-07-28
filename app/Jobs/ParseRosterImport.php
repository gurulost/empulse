<?php

namespace App\Jobs;

use App\Models\RosterImport;
use App\Services\RosterImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ParseRosterImport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public int $uniqueFor = 900;

    public function __construct(public int $rosterImportId) {}

    public function uniqueId(): string
    {
        return (string) $this->rosterImportId;
    }

    public function handle(RosterImportService $service): void
    {
        $import = RosterImport::find($this->rosterImportId);
        if (! $import || $import->status !== 'parsing') {
            return;
        }

        $import->touch();
        $service->parse($import);
    }

    public function failed(Throwable $exception): void
    {
        RosterImport::whereKey($this->rosterImportId)->update([
            'status' => 'failed',
            'source_csv' => null,
            'failure_summary' => 'Roster parsing failed unexpectedly. Review the job logs before retrying.',
            'failed_at' => now(),
        ]);
    }
}
