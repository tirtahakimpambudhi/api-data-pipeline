<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PendingAdminRegistrations extends Model
{
    use HasUlids;
    protected $table = 'pending_admin_registrations';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    protected $fillable = [
        'name','email','password_hash','role_name','nonce','expires_at',
    ];
    public $incrementing = false;
    protected $casts = [
        'expires_at' => 'datetime',
        'approved_at'=> 'datetime',
        'rejected_at'=> 'datetime',
    ];

    public function isExpired(): bool {
        return now()->greaterThan($this->expires_at);
    }
    public function isFinalized(): bool {
        return !is_null($this->approved_at) || !is_null($this->rejected_at);
    }
}
