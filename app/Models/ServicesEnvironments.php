<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Cache;

class ServicesEnvironments extends Model
{
    /** @use HasFactory<\Database\Factories\ServicesEnvironmentsFactory> */
    use HasFactory;
    protected $table = 'services_environments';
    protected $primaryKey = 'id';

    protected $fillable = [
        'service_id',
        'environment_id',
    ];

    protected $appends = ['name'];

    protected function name(): Attribute
    {
        return Attribute::get(function (): string {

            if (
                $this->relationLoaded('service') &&
                $this->service &&
                $this->service->relationLoaded('namespace') &&
                $this->service->namespace &&
                $this->relationLoaded('environment') &&
                $this->environment
            ) {
                return "{$this->service->namespace->name}.{$this->service->name}[{$this->environment->name}]";
            }

            return '';
        });
    }

    public function service() : BelongsTo {
        return $this->belongsTo(Services::class, 'service_id');
    }
    public function environment() : BelongsTo {
        return $this->belongsTo(Environments::class, 'environment_id');
    }
    public function channels() : BelongsToMany
    {
        return $this->belongsToMany(ServicesEnvironments::class, 'configurations', 'service_environment_id', 'channel_id')
            ->withPivot('id');
    }
    public function configurations() : HasMany
    {
        return $this->hasMany(Configurations::class, 'service_environment_id');
    }
}
