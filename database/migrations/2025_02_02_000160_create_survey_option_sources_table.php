<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_option_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_item_id');
            $table->string('kind');
            $table->json('config')->nullable();
            $table->timestamps();

            $table->foreign('survey_item_id')
                ->references('id')
                ->on('survey_items')
                ->onDelete('cascade');
            $table->unique('survey_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_option_sources');
    }
};
