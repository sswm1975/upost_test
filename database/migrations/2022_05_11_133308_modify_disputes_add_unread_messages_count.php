<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyDisputesAddUnreadMessagesCount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->unsignedTinyInteger('unread_messages_count')->default(0)->comment('Кол-во непрочитанных сообщений менеджером');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn('unread_messages_count');
        });
    }
}
