<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTransactionsTableAddFeeTax extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('order_amount', 10)
                ->default(0.00)
                ->comment('Сумма заказа')
                ->after('amount');

            $table->decimal('delivery_amount', 10)
                ->default(0.00)
                ->comment('Стоимость доставки')
                ->after('order_amount');

            $table->decimal('liqpay_fee', 10)
                ->default(0.00)
                ->comment('Комиссия Liqpay')
                ->after('delivery_amount');

            $table->decimal('service_fee', 10)
                ->default(0.00)
                ->comment('Комиссия сервиса')
                ->after('liqpay_fee');

            $table->decimal('export_tax', 10)
                ->default(0.00)
                ->comment('Налог на вывоз товара')
                ->after('service_fee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('order_amount');
            $table->dropColumn('delivery_amount');
            $table->dropColumn('liqpay_fee');
            $table->dropColumn('service_fee');
            $table->dropColumn('export_tax');
        });
    }
}
