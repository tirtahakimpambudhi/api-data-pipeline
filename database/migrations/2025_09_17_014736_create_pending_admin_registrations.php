<?php

use App\Constants\RolesTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::create('pending_admin_registrations', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password_hash');
            $t->string('role_name')->default(RolesTypes::ALMIGHTY);
            $t->string('nonce')->unique();
            $t->timestamp('expires_at');
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pending_admin_registrations');
    }
};
