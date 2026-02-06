<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_version_id');
            $table->unsignedBigInteger('survey_page_id');
            $table->unsignedBigInteger('survey_section_id')->nullable();
            $table->string('qid');
            $table->string('type');
            $table->text('question');
            $table->json('scale_config')->nullable();
            $table->json('response_config')->nullable();
            $table->json('display_logic')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('survey_version_id')
                ->references('id')
                ->on('survey_versions')
                ->onDelete('cascade');
            $table->foreign('survey_page_id')
                ->references('id')
                ->on('survey_pages')
                ->onDelete('cascade');
            $table->foreign('survey_section_id')
                ->references('id')
                ->on('survey_sections')
                ->onDelete('cascade');
            $table->unique(['survey_version_id', 'qid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_items');
    }
};
