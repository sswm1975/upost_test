<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyDisputeProblemsAddRateStatusAndInitiator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispute_problems', function (Blueprint $table) {
            $table->enum('initiator', ['customer', 'performer'])->comment('Инициатор: Исполнитель или Заказчик')->after('id');
            $table->enum('rate_status', ['accepted', 'buyed', 'successful'])->comment('Статус ставки')->after('initiator');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispute_problems', function (Blueprint $table) {
            $table->dropColumn(['initiator', 'rate_status']);
        });
    }
}
