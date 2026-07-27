<?php

namespace Tests\Feature;

use App\Models\InterventionPlaybookVersion;
use App\Services\InterventionPlaybookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterventionPlaybookTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_returns_only_published_playbooks_eligible_for_metric(): void
    {
        $service = app(InterventionPlaybookService::class);

        $opportunity = $service->publishedForMetric('opportunity.WCA_REL');

        $this->assertCount(1, $opportunity);
        $this->assertSame('work_design_small_test', $opportunity->first()->intervention_key);
        $this->assertNotEmpty($opportunity->first()->steps);
        $this->assertStringContainsString('not proof', $opportunity->first()->claims_limit);
    }

    public function test_catalog_rejects_playbook_from_another_metric_family(): void
    {
        $service = app(InterventionPlaybookService::class);
        $playbook = InterventionPlaybookVersion::where('intervention_key', 'team_norms_reset')->firstOrFail();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not eligible');

        $service->resolveApplicable($playbook->id, 'opportunity.WCA_REL');
    }
}
