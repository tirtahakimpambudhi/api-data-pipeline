<?php

namespace Database\Factories;

use App\Models\Namespaces;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Services>
 */
class ServicesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $namespace = Namespaces::factory();
        return [
            'name' => $this->faker->word(),
            'namespace_id' => fn (array $attrs) =>
                $attrs['namespace_id'] ?? Namespaces::factory(),
        ];
    }
}
