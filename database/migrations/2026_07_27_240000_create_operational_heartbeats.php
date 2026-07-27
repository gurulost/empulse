<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->string('process', 32)->unique();
            $table->uuid('instance_id')->nullable();
            $table->timestamp('last_seen_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_heartbeats');
    }
};
