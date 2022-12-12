<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyDisputesAddTextAndImages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('disputes')->truncate();

        Schema::table('disputes', function (Blueprint $table) {
            $table->dropForeign('FK_disputes_messages');
            $table->dropIndex('FK_disputes_messages');

            $table->dropColumn('message_id');
            $table->text('text')->comment('Текст спора')->after('chat_id');
            $table->json('images')->nullable()->comment('Фотографии купленного заказа')->after('text');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedInteger('dispute_id')->nullable()->comment('Код спора')->after('user_id');

            $table->foreign('dispute_id', 'FK_messages_disputes')->references('id')->on('disputes')->onDelete('cascade');
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
            $table->dropColumn(['text', 'images']);
            $table->unsignedBigInteger('message_id')->comment('Код сообщения')->after('chat_id');

            $table->foreign('message_id', 'FK_disputes_messages')->references('id')->on('messages')->onDelete('cascade');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign('FK_messages_disputes');

            $table->dropColumn('dispute_id');
        });
    }
}
