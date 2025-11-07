<?php

namespace App\Casts;

use App\Http\Resources\Configurations\Source;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SourceJsonCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes):? Source
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return Source::fromJson($value);
        }

        if (is_array($value)) {
            return Source::fromArray($value);
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

        if ($value instanceof Source) {
            return $value->toJson(false);
        }

        if (is_array($value)) {
            return Source::fromArray($value)->toJson(false);
        }

        if (is_string($value)) {
            Source::fromJson($value);
            return $value;
        }

        throw new InvalidArgumentException(
            "Column '{$key}' expects Source|array|string|null."
        );
    }
}
