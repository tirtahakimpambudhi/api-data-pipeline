<?php

namespace Database\Factories;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Traits\Helpers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permissions>
 */
class PermissionsFactory extends Factory
{
    use Helpers;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $pairs = null;


        if ($pairs === null) {
            $pairs = $this->crossComboArr(ResourcesTypes::all(), ActionsTypes::all(), 'resource_type', 'action');
        }


        $pair = array_pop($pairs);

        return [
            'resource_type' => $pair['resource_type'],
            'action'        => $pair['action'],
            'description'   => $this->faker->sentence(),
        ];
    }
}
