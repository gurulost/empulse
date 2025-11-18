<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_versions', function (Blueprint $table) {
            $table->id();
            $table->string('instrument_id');
            $table->string('version')->default('1.0.0');
            $table->string('title');
            $table->date('created_utc')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('source_note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_versions');
    }
};
