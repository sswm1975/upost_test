<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteStripeColumnsInTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->dropColumnIfExists('users', 'stripe_payment_method');
        $this->dropColumnIfExists('transactions', 'purchase_error');
        $this->dropColumnIfExists('transactions', 'purchase_exception');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }

    public function dropColumnIfExists($table, $column)
    {
        if (Schema::hasColumn($table, $column))
        {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
}
