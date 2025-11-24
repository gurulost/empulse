<?php

namespace Tests\Concerns;

use App\Models\Survey;
use App\Models\SurveyVersion;

trait CreatesSurveyFixtures
{
    protected function setUpSurveyFixtures(): void
    {
        if (!Survey::where('is_default', true)->exists()) {
            Survey::factory()->create([
                'title' => 'Default Survey',
                'is_default' => true,
                'status' => 'published',
            ]);
        }

        if (!SurveyVersion::where('is_active', true)->exists()) {
            SurveyVersion::factory()->create([
                'version' => 'v1',
                'title' => 'Default Survey Version',
                'is_active' => true,
            ]);
        }
    }
}
