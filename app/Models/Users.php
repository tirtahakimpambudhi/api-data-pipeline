<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UsersFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Users extends Authenticatable
{
    /** @use HasFactory<UsersFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot() :void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
        });
    }

    public function role() : BelongsTo
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    public function hasPermission(string $resourceType, string $action): bool {
        if (! $this->role_id) return false;

        $key = "role_perms:{$this->role_id}";
        $abilities = Cache::rememberForever($key, function () {
            return Permissions::query()
                ->select('resource_type','action')
                ->join('role_permission','permissions.id','=','role_permission.permission_id')
                ->where('role_permission.role_id',$this->role_id)
                ->get()
                ->map(fn($p) => "{$p->resource_type}.{$p->action}")
                ->all();
        });

        return in_array("{$resourceType}.{$action}", $abilities, true);
    }
}
