<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->string('title');
            $table->string('key')->unique();
            $table->string('question_type')->default('scale');
            $table->json('metrics')->nullable();
            $table->text('help_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('survey_questions');
    }
};
