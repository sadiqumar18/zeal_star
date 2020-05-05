<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUssdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
    public function up()
    {
        Schema::create('ussds', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned();
            $table->string('reference');
            $table->string('amount');
            $table->string('phone');
            $table->string('bundle_code')->default('Null');
            $table->string('response')->default('Pending...');
            $table->string('response_time');
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
        Schema::dropIfExists('ussds');
    }
}
