<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsedPinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('used_pins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('serial_number')->unique();
            $table->string('pin_number')->unique();
            $table->string('value')->unique();
            $table->string('phone');
            $table->string('time_used');
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
        Schema::dropIfExists('used_pins');
    }
}
