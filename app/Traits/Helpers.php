<?php

namespace App\Traits;
use App\Exceptions\AppServiceException;
use App\Exceptions\NotFoundServiceException;

use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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


    public function applyPagination(Builder $query, int $page, int $size): LengthAwarePaginator
    {
        Log::info("Apply pagination: page={$page}, size={$size}");
        $paginator  = $query->paginate(perPage: $size, page: $page);
        $totalPages = max(1, (int) ceil($paginator->total() / $paginator->perPage()));
        if ($page > $totalPages) {
            Log::error("Page not found with current page {$page}");
            throw new NotFoundServiceException("Page not found with current page {$page}.");
        }
        Log::info("Successfully fetched page={$page} size={$size}");
        return $paginator;
    }


}
