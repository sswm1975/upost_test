<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyChatsTableAddLockStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->enum('lock_status', [
                    'without_lock',
                    'lock_add_message_only_customer',
                    'lock_add_message_only_performer',
                    'lock_add_message_all',
                    'permit_one_message_only_customer',
                    'permit_one_message_only_performer',
                    'permit_one_message_all',
                ])
                ->default('without_lock')
                ->comment('Статус блокировки')
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('lock_status');
        });
    }
}
