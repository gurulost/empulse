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
        Schema::create('company_table_example', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('manager');
            $table->string('teamlead');
            $table->string('employee');
            $table->string('chief');
        });

        DB::table('company_table_example')->insert([
            [
                'name' => 'Ludmila Zabava',
                'email' => 'ludmila_0701@gmail.com',
                'manager' => 'yes',
                'teamlead' => 'no',
                'employee' => 'no',
                'chief' => 'no',
            ],
            [
                'name' => 'John Smith',
                'email' => 'smith1976@gmail.com',
                'manager' => 'no',
                'teamlead' => 'no',
                'employee' => 'no',
                'chief' => 'yes',
            ],
            [
                'name' => 'Bred Tir',
                'email' => 'bred_no_pitt@gmail.com',
                'manager' => 'no',
                'teamlead' => 'yes',
                'employee' => 'no',
                'chief' => 'no',
            ],
            [
                'name' => 'Alexander Yalovoy',
                'email' => 'alex_007@gmail.com',
                'manager' => 'no',
                'teamlead' => 'no',
                'employee' => 'yes',
                'chief' => 'no',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_table_example');
    }
};
