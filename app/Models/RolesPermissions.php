<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolesPermissions extends Model
{
    /** @use HasFactory<\Database\Factories\RolesPermissionsFactory> */
    use HasFactory;
    protected $table = 'roles_permissions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'role_id',
        'permission_id',
    ];
    public function role() : BelongsTo {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
    }

    public function permission() : BelongsTo {
        return $this->belongsTo(Permissions::class, 'permission_id', 'id');
    }
}
