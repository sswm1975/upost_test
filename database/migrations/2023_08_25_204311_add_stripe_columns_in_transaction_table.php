<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStripeColumnsInTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->comment('Идентификатор сеанса оплаты в платежной системе Stripe');
            $table->string('stripe_payment_intent_id')->nullable()->comment('Идентификатор платежного намерения в платежной системе Stripe (используется при Refund)');
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
            $table->dropColumn('stripe_checkout_session_id');
            $table->dropColumn('stripe_payment_intent_id');
        });
    }
}
