<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('response_id');
            $table->unsignedBigInteger('question_id');
            $table->string('question_key');
            $table->text('value')->nullable();
            $table->decimal('value_numeric', 8, 2)->nullable();
            $table->timestamps();

            $table->foreign('response_id')->references('id')->on('survey_responses')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('survey_questions')->onDelete('cascade');
            $table->index(['question_key', 'value_numeric']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('survey_answers');
    }
};
