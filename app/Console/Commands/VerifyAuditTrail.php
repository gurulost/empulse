<?php

namespace App\Console\Commands;

use App\Services\AuditTrailService;
use Illuminate\Console\Command;

class VerifyAuditTrail extends Command
{
    protected $signature = 'audit:verify {--company= : Verify one company stream instead of the platform stream}';

    protected $description = 'Verify sequence and HMAC integrity of an append-only audit stream';

    public function handle(AuditTrailService $audit): int
    {
        $company = $this->option('company');
        $result = $audit->verify($company === null ? null : (int) $company);
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result['valid'] ? self::SUCCESS : self::FAILURE;
    }
}
