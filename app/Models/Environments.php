<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Environments extends Model
{
    /** @use HasFactory<\Database\Factories\EnvironmentsFactory> */
    use HasFactory;
    protected $table = 'environments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
    ];
    public function services() :BelongsToMany
    {
        return $this->belongsToMany(Services::class, 'services_environments', 'environment_id', 'service_id')
            ->withPivot('id');
    }
    public function servicesEnvironments() : HasMany
    {
        return $this->hasMany(ServicesEnvironments::class, 'environment_id');
    }

    public function configurations() : HasManyThrough
    {
        return $this->hasManyThrough(
            Configurations::class,
            ServicesEnvironments::class,
            'environment_id',
            'service_environment_id',
            'id',
            'id'
        );
    }
}
