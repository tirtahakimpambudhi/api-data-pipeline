<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roles extends Model
{
    /** @use HasFactory<\Database\Factories\RolesFactory> */
    use HasFactory;
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
    ];

    public function permissions(): BelongsToMany {
        return $this->belongsToMany(Permissions::class, 'roles_permissions', 'role_id', 'permission_id')
            ->withPivot('id');
    }

    public function rolesPermissions() : HasMany
    {
        return $this->hasMany(RolesPermissions::class, 'role_id');
    }


    public function users() : HasMany {
        return $this->hasMany(
            Users::class,
            'role_id',
        );
    }
}
