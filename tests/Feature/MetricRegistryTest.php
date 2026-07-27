<?php

namespace Tests\Feature;

use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\MetricRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MetricRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cli_activation_requires_named_approver_and_change_summary(): void
    {
        $this->assertSame(1, Artisan::call('survey:import', [
            'path' => base_path('survey_instrument.json'),
            '--activate' => true,
        ]));
        $this->assertDatabaseCount('survey_versions', 1);
        $this->assertDatabaseMissing('survey_versions', [
            'instrument_id' => 'empulse_workfit_baseline',
        ]);
    }

    public function test_canonical_instrument_and_registry_are_complete_hash_bound_and_immutable_by_version(): void
    {
        $payload = json_decode(
            file_get_contents(base_path('survey_instrument.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
            'status' => 'active',
        ]);
        $this->assertSame(0, Artisan::call('survey:import', [
            'path' => base_path('survey_instrument.json'),
            '--activate' => true,
            '--approved-by' => $admin->id,
            '--change-summary' => 'Publish canonical instrument for registry integrity verification.',
        ]));
        $version = SurveyVersion::where('instrument_id', $payload['instrument_id'])
            ->where('version', $payload['version'])
            ->firstOrFail();
        $this->assertSame('published', $version->publication_status);
        $this->assertSame($admin->id, $version->approved_by);
        $this->assertNotEmpty($version->change_summary);
        $registry = app(MetricRegistryService::class);

        $registry->assertInstrumentCompatible($version);
        $required = $registry->requiredQuestionIds();

        $this->assertCount(62, $required);
        $this->assertCount(62, array_unique($required));
        $published = $registry->publishedVersion();
        $this->assertSame($registry->hash(), $published->definition_hash);
        $this->assertSame('published', $published->status);
        $this->assertDatabaseCount('metric_registry_versions', 1);
        $this->assertSame($published->id, $registry->publishedVersion()->id);
    }
}
