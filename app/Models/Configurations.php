<?php

namespace App\Models;

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

    public function serviceEnvironment() : BelongsTo {
        return $this->belongsTo(ServicesEnvironments::class, 'service_environment_id');
    }

    public function channel() : BelongsTo {
        return $this->belongsTo(Channels::class, 'channel_id');
    }

}
