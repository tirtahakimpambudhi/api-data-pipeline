<?php

namespace App\Casts;

use App\Http\Resources\Configurations\Destination;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class DestinationJsonCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Destination
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return Destination::fromJson($value);
        }

        if (is_array($value)) {
            return Destination::fromArray($value);
        }

        throw new InvalidArgumentException("Column '{$key}' must be JSON string or array.");
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Destination) {
            return $value->toJson(false);
        }

        if (is_array($value)) {
            return Destination::fromArray($value)->toJson(false);
        }

        if (is_string($value)) {
            Destination::fromJson($value);
            return $value;
        }

        throw new InvalidArgumentException(
            "Column '{$key}' expects Destination|array|string|null."
        );
    }
}
