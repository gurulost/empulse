<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_scale_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_version_id');
            $table->string('preset_key');
            $table->json('config');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('survey_version_id')
                ->references('id')
                ->on('survey_versions')
                ->onDelete('cascade');
            $table->unique(['survey_version_id', 'preset_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_scale_presets');
    }
};
