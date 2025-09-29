<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permissions extends Model
{
    /** @use HasFactory<\Database\Factories\PermissionsFactory> */
    use HasFactory;

    protected $table = 'permissions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'resource_type',
        'action',
        'description',
    ];

    public function roles() : BelongsToMany
    {
        return $this->belongsToMany(Roles::class, 'roles_permissions', 'permission_id', 'role_id')
            ->withPivot('id');
    }

    public function rolesPermissions() : HasMany
    {
        return $this->hasMany(RolesPermissions::class, 'permission_id');
    }
}
