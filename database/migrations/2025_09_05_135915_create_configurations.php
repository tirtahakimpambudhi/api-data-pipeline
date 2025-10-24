<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_environment_id')->references('id')->on('services_environments')->onDelete('cascade');
            $table->foreignId('channel_id')->references('id')->on('channels')->onDelete('cascade');
//            $table->unique(['service_environment_id', 'channel_id']);
            $table->text('source')->nullable(false);
            $table->text('destination')->nullable(false);
            $table->string('cron_expression')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
