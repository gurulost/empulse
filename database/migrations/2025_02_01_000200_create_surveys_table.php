<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('published');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('is_default');
        });
    }

    public function down()
    {
        Schema::dropIfExists('surveys');
    }
};
