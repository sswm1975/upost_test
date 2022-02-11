<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyRatesAddChatId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_id')
                ->nullable()
                ->after('order_id')
                ->comment('Код чата');

            $table->foreign('chat_id', 'FK_rates_chats')
                ->references('id')
                ->on('chats')
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
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('chat_id');
        });
    }
}
