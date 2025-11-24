<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'is_default' => false,
            'status' => 'draft',
            'metadata' => [],
        ];
    }
}
