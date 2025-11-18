<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_page_id');
            $table->string('section_id');
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('survey_page_id')
                ->references('id')
                ->on('survey_pages')
                ->onDelete('cascade');
            $table->unique(['survey_page_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_sections');
    }
};
