<?php

namespace Database\Factories;

use App\Models\Environments;
use App\Models\Services;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServicesEnvironments>
 */
class ServicesEnvironmentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => fn (array $attrs) =>
                $attrs['service_id'] ?? Services::factory(),

            'environment_id' => fn (array $attrs) =>
                $attrs['environment_id'] ?? Environments::factory(),
        ];
    }
}
