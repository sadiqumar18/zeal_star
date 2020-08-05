<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOnlineDataTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('online_data_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->Integer('user_id')->unsigned();
            $table->string('bundle');
            $table->string('number',13);
            $table->string('referrence')->unique();
            $table->string('network',13);
            $table->decimal('price')->default(0);
            $table->string('status')->default('processing');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
            $table->timestamps();

            $table->integer('payment_status')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('online_data_transactions');
    }
}
