<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['runtime.audit_hash_key' => str_repeat('k', 32)]);
    }

    public function test_company_audit_stream_is_chained_attributable_and_verifiable(): void
    {
        $company = Companies::create([
            'title' => 'Audit Co',
            'manager' => 'Owner',
            'manager_email' => 'owner@audit.test',
        ]);
        $actor = User::factory()->create([
            'company_id' => $company->id,
            'role' => 1,
            'status' => 'active',
        ]);
        $audit = app(AuditTrailService::class);

        $first = $audit->record(
            'member.invited',
            $actor,
            $company->id,
            User::class,
            123,
            ['role' => 4]
        );
        $second = $audit->record(
            'member.deactivated',
            $actor,
            $company->id,
            User::class,
            123
        );

        $this->assertSame($first->sequence + 1, $second->sequence);
        $this->assertSame($first->event_hash, $second->previous_hash);
        $this->assertSame($actor->id, $second->actor_user_id);
        $this->assertTrue($audit->verify($company->id)['valid']);
        $this->artisan('audit:verify', ['--company' => $company->id])->assertSuccessful();
    }

    public function test_normal_model_paths_cannot_edit_or_delete_audit_events(): void
    {
        $event = app(AuditTrailService::class)->record('platform.checked');

        try {
            $event->update(['action' => 'tampered']);
            $this->fail('Audit update should have failed.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        $this->expectException(\LogicException::class);
        $event->delete();
    }

    public function test_verifier_detects_out_of_band_database_tampering(): void
    {
        $audit = app(AuditTrailService::class);
        $event = $audit->record('platform.checked', null, null, null, null, ['result' => 'ok']);

        DB::table('audit_events')->where('id', $event->id)->update([
            'action' => 'platform.tampered',
        ]);

        $result = $audit->verify();
        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['failed_event_id']);
    }
}
