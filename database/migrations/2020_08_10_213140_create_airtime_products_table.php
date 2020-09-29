<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAirtimeProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('airtime_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('network')->unique();
            $table->string('code')->unique()->nullable();
            $table->decimal('standard')->default(0);
            $table->decimal('agent')->default(0);
            $table->decimal('merchant')->default(0);
            $table->decimal('reseller')->default(0);
            $table->decimal('vendor')->default(0);
            $table->integer('is_available')->default(0);
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
        Schema::dropIfExists('airtime_products');
    }
}
