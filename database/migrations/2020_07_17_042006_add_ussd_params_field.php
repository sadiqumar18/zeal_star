<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUssdParamsField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_products', function (Blueprint $table) {
            $table->json('ussd_param')->after('code')->nullable();
            $table->dropUnique('data_products_code_unique');
            $table->boolean('is_suspended')->default(false);
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
            $table->dropColumn('ussd_param');
            $table->dropColumn('is_suspended');
        });
    }
}
