<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channels extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelsFactory> */
    use HasFactory;
    protected $table = 'channels';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
    ];

    public function servicesEnvironments() : BelongsToMany
    {
        return $this->belongsToMany(ServicesEnvironments::class, 'configurations', 'channel_id', 'service_environment_id');
    }

    public function configurations() : HasMany
    {
        return $this->hasMany(Configurations::class, 'channel_id');
    }
}
