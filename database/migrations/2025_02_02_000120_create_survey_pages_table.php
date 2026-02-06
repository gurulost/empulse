<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_version_id');
            $table->string('page_id');
            $table->string('title');
            $table->string('attribute_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('survey_version_id')
                ->references('id')
                ->on('survey_versions')
                ->onDelete('cascade');
            $table->unique(['survey_version_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_pages');
    }
};
