<?php

namespace App\Traits;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use Faker\Factory as Faker;
trait Helpers
{
    public function crossComboArr(
        array $left,
        array $right,
        string $leftKey,
        string $rightKey,
        bool $shuffle = false,
    ): array {
        $c = collect($left)
            ->crossJoin($right)
            ->map(fn ($item) => [$leftKey => $item[0], $rightKey => $item[1]]);

        if ($shuffle) $c = $c->shuffle();

        return $c->values()->all();
    }

    public function randomOneWayComboArr(
        array $left,
        array $right,
        string $leftKey,
        string $rightKey,
        bool $shuffle = false,
    )
    {
        $faker = Faker::create();
        $c = collect($left)
            ->map(fn ($item) => [$leftKey => $item, $rightKey => $right[$faker->numberBetween(0, (count($right) - 1))]]);

        if ($shuffle) $c = $c->shuffle();

        return $c->values()->all();
    }
}
