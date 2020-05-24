<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_products', function (Blueprint $table) {
            $table->decimal('standard')->default(0);
            $table->decimal('agent')->default(0);
            $table->decimal('merchant')->default(0);
            $table->decimal('reseller')->default(0);
            $table->decimal('vendor')->default(0);
            //$table->dropColumn('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_products', function (Blueprint $table) {
            //
        });
    }
}
