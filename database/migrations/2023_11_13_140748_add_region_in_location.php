<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegionInLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('region', 100)->nullable()->comment('Наименование региона (область, штат)')->after('name_ru');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->string('from_region', 100)->nullable()->comment('Наименование региона старта')->after('from_country_id');
            $table->string('to_region', 100)->nullable()->comment('Наименование региона окончания')->after('to_country_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('from_region', 100)->nullable()->comment('Наименование региона откуда доставить заказ')->after('from_country_id');
            $table->string('to_region', 100)->nullable()->comment('Наименование региона куда доставить заказ')->after('to_country_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('region');
        });
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['from_region', 'to_region']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['from_region', 'to_region']);
        });
    }
}
