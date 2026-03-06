<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name', 80)->index();
            $table->string('context_surface', 80)->index();
            $table->string('task_id', 80)->nullable()->index();
            $table->string('user_segment', 24)->nullable();
            $table->string('guidance_level', 24)->nullable();
            $table->string('session_id', 120)->nullable()->index();
            $table->unsignedSmallInteger('attempt_index')->default(1);
            $table->unsignedInteger('time_since_session_start_sec')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_events');
    }
};
