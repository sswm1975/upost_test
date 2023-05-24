<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardInfoInUserCchangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_changes', function (Blueprint $table) {
            $table->unsignedTinyInteger('card_exp_month')->nullable()->comment('Месяц окончания банковской карточки')->after('card_name');
            $table->unsignedSmallInteger('card_exp_year')->nullable()->comment('Год окончания банковской карточки')->after('card_exp_month');
            $table->string('card_cvc', 3)->nullable()->comment('CVC-код на банковской карточке')->after('card_exp_year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_changes', function (Blueprint $table) {
            $table->dropColumn(['card_exp_month', 'card_exp_year', 'card_cvc']);
        });
    }
}
