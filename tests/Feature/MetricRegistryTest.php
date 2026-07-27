<?php

namespace Tests\Feature;

use App\Models\SurveyVersion;
use App\Services\MetricRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MetricRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_instrument_and_registry_are_complete_hash_bound_and_immutable_by_version(): void
    {
        $payload = json_decode(
            file_get_contents(base_path('survey_instrument.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->assertSame(0, Artisan::call('survey:import', [
            'path' => base_path('survey_instrument.json'),
            '--activate' => true,
        ]));
        $version = SurveyVersion::where('instrument_id', $payload['instrument_id'])
            ->where('version', $payload['version'])
            ->firstOrFail();
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
