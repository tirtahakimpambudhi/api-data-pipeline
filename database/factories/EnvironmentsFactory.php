<?php

namespace Database\Factories;

use App\Constants\EnvironmentsTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Environments>
 */
class EnvironmentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique(true)->randomElement(EnvironmentsTypes::all()),
        ];
    }
}
