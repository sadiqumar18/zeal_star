<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRoutesToSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('mtn_data_route')->default("telerivet");
            $table->string('airtel_data_route')->default("telerivet");
            $table->string('glo_data_route')->default("telerivet");
            $table->string('etisalat_data_route')->default("telerivet");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('mtn_data_route');
            $table->dropColumn('airtel_data_route');
            $table->dropColumn('glo_data_route');
            $table->dropColumn('etisalat_data_route');
        });
    }
}
