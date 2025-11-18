<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_wave_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_wave_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('survey_wave_id')->references('id')->on('survey_waves')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_wave_logs');
    }
};
