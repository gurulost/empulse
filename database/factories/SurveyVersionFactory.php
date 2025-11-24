<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SurveyVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'instrument_id' => (string) Str::uuid(),
            'version' => 'v'.$this->faker->numberBetween(1, 5),
            'title' => $this->faker->sentence(3),
            'created_utc' => now(),
            'is_active' => false,
            'source_note' => $this->faker->sentence(),
            'meta' => [],
        ];
    }
}
