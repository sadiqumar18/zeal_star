<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMegabyteToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_transactions', function (Blueprint $table) {
            $table->integer('megabytes')->default(0)->after('bundle');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_transactions', function (Blueprint $table) {
            $table->dropColumn('megabytes');
        });
    }
}
