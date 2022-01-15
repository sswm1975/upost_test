<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('FK_orders_categories');
            $table->dropIndex('FK_orders_categories');

            $table->unsignedTinyInteger('wait_range_id')->nullable()->after('tilldate')->comment('Код диапазона ожидания');
            $table->dropColumn(['category_id', 'size', 'weight', 'fromdate', 'from_address', 'to_address', 'personal_price']);
            $table->renameColumn('tilldate', 'deadline');

            $table->foreign('wait_range', 'FK_orders_wait_range')
                ->references('id')->on('wait_range_id')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
