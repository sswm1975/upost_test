<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteOrdersTableUserPriceAndUserCurrency extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['user_price', 'user_currency']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('user_price', 10)->default(0.00)->comment('Сумма дохода')->after('wait_range_id');
            $table->char('user_currency', 2)->nullable()->comment('Валюта дохода')->after('user_price_usd');
        });
    }
}
