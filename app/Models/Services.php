<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Services extends Model
{
    /** @use HasFactory<\Database\Factories\ServicesFactory> */
    use HasFactory;
    protected $table = 'services';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'namespace_id',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::get(function ():string {
            if (!$this->id || !$this->namespace_id) {
                return '';
            }

            $this->loadMissing([
                'namespace',
            ]);

            return "{$this->namespace->name}.{$this->name}";
        });
    }

    public function namespace() :BelongsTo {
        return $this->belongsTo(Namespaces::class, 'namespace_id');
    }
    public function environments() :BelongsToMany
    {
        return $this->belongsToMany(Environments::class, 'services_environments', 'service_id', 'environment_id')
            ->withPivot('id');
    }
    public function servicesEnvironments() : HasMany {
        return $this->hasMany(ServicesEnvironments::class, 'service_id');
    }
    public function configurations() : HasManyThrough
    {
        return $this->hasManyThrough(
            Configurations::class,
            ServicesEnvironments::class,
            'service_id',
            'service_environment_id',
            'id',
            'id'
        );
    }
}
