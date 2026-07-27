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

        $this->assertCount(2, $opportunity);
        $workDesign = $opportunity->firstWhere('intervention_key', 'work_design_small_test');
        $investigateFirst = $opportunity->firstWhere('intervention_key', 'investigate_first');
        $this->assertNotNull($workDesign);
        $this->assertNotNull($investigateFirst);
        $this->assertNotEmpty($workDesign->steps);
        $this->assertStringContainsString('not proof', $workDesign->claims_limit);
        $this->assertSame('practice-informed-unvalidated', $workDesign->evidence_grade);
        $this->assertStringContainsString('independent methodology approval pending', $workDesign->evidence_source);
        $this->assertNotEmpty($workDesign->applicability);
        $this->assertStringContainsString('does not establish', $workDesign->limitations);
        $this->assertStringContainsString('uncertainty', strtolower($investigateFirst->claims_limit));
    }

    public function test_catalog_rejects_playbook_from_another_metric_family(): void
    {
        $service = app(InterventionPlaybookService::class);
        $playbook = InterventionPlaybookVersion::where('intervention_key', 'team_norms_reset')->firstOrFail();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not eligible');

        $service->resolveApplicable($playbook->id, 'opportunity.WCA_REL');
    }

    public function test_published_playbook_version_cannot_be_changed_or_deleted(): void
    {
        $playbook = InterventionPlaybookVersion::where('intervention_key', 'work_design_small_test')->firstOrFail();

        try {
            $playbook->update(['title' => 'Silently changed']);
            $this->fail('Published playbook update should have been rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('immutable');
        $playbook->delete();
    }
}
