<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_worker', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company_title')->nullable();
            $table->string('department')->nullable();
            $table->string('supervisor')->nullable();
            $table->integer('role');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_worker');
    }
};
