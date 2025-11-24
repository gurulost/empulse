<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use Tests\Concerns\CreatesSurveyFixtures;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use CreatesSurveyFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSurveyFixtures();
    }
}
