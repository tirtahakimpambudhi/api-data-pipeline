<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Configurations extends Model
{
    /** @use HasFactory<\Database\Factories\ConfigurationsFactory> */
    use HasFactory;
    protected $table = 'configurations';
    protected $primaryKey = 'id';
    protected $fillable = [
        'service_environment_id',
        'channel_id'
    ];

    protected $appends = ['name'];

    protected function name(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->relationLoaded('serviceEnvironment') && $this->serviceEnvironment && $this->relationLoaded('channel') && $this->channel) {
                $svcEnv = $this->serviceEnvironment;

            if (
                $svcEnv->relationLoaded('service') &&
                $svcEnv->service &&
                $svcEnv->service->relationLoaded('namespace') &&
                $svcEnv->service->namespace &&
                $svcEnv->relationLoaded('environment') &&
                $svcEnv->environment
            ) {
                return "{$svcEnv->service->namespace->name}.{$svcEnv->service->name}[{$svcEnv->environment->name}] | {$this->channel->name}";
            }

                return '';
            }
            return '';
        });
    }


    public function serviceEnvironment() : BelongsTo {
        return $this->belongsTo(ServicesEnvironments::class, 'service_environment_id');
    }

    public function channel() : BelongsTo {
        return $this->belongsTo(Channels::class, 'channel_id');
    }

}
