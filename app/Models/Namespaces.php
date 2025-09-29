<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Namespaces extends Model
{
    /** @use HasFactory<\Database\Factories\NamespacesFactory> */
    use HasFactory;
    protected $table = 'namespaces';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
    ];

    public function services() : HasMany
    {
        return $this->hasMany(Services::class, 'namespace_id');
    }

    public function servicesEnvironments() : HasManyThrough
    {
        return $this->hasManyThrough(
            ServicesEnvironments::class,
            Services::class,
            'namespace_id',
            'service_id',
            'id',
            'id'
        );
    }
}
