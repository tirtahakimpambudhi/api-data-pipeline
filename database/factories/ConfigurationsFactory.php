<?php

namespace Database\Factories;

use App\Models\Channels;
use App\Models\Services;
use App\Models\ServicesEnvironments;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Configurations>
 */
class ConfigurationsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_environment_id' => fn (array $attrs) =>
                $attrs['service_environment_id'] ?? ServicesEnvironments::factory(),

            'channel_id' => fn (array $attrs) =>
                $attrs['channel_id'] ?? Channels::factory(),
        ];
    }
}
