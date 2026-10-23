<?php

namespace Tests\Unit;

use App\Support\RuntimeSecurityPolicy;
use PHPUnit\Framework\TestCase;

class RuntimeSecurityPolicyTest extends TestCase
{
    public function test_each_supported_branch_rejects_the_last_vulnerable_patch(): void
    {
        foreach (['8.2.34', '8.3.35', '8.4.26', '8.5.11', '8.1.35', '8.6.0', '8.2.35RC1'] as $version) {
            $this->assertFalse(RuntimeSecurityPolicy::supportsPhp($version), $version);
        }
        foreach (['8.2.35', '8.3.36', '8.4.27', '8.5.12', '8.2.36', '8.5.13'] as $version) {
            $this->assertTrue(RuntimeSecurityPolicy::supportsPhp($version), $version);
        }
    }
}
