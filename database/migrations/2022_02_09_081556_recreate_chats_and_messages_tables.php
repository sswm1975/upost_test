<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateChatsAndMessagesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chats');

        $this->createTableChats();
        $this->createTableMessages();
    }

    private function createTableChats()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Код');
            $table->unsignedInteger('route_id')->comment('Код маршрута');
            $table->unsignedInteger('order_id')->comment('Код заказа');
            $table->unsignedInteger('performer_id')->comment('Владелец маршрута (Исполнитель)');
            $table->unsignedInteger('customer_id')->comment('Владелец заказа (Заказчик)');
            $table->unsignedTinyInteger('performer_unread_count')->default(0)->comment('Кол-во непрочитанных сообщений исполнителем');
            $table->unsignedTinyInteger('customer_unread_count')->default(0)->comment('Кол-во непрочитанных сообщений заказчиком');
            $table->enum('status', ['active', 'closed'])->default('active')->comment('Статус');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');

            $table->foreign('route_id', 'FK_chats_routes')
                ->references('id')
                ->on('routes')
                ->onDelete('cascade');

            $table->foreign('order_id', 'FK_chats_orders')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');

            $table->foreign('performer_id', 'FK_chats_performers')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('customer_id', 'FK_chats_customers')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        DB::statement("ALTER TABLE chats COMMENT = 'Чаты'");
    }

    private function createTableMessages()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Код');
            $table->unsignedBigInteger('chat_id')->comment('Код чата');
            $table->unsignedInteger('user_id')->comment('Автор сообщения');
            $table->text('text')->comment('Текст сообщения');
            $table->json('files')->nullable()->comment('Прикрепленные файлы');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');

            $table->foreign('chat_id', 'FK_messages_chats')
                ->references('id')
                ->on('chats')
                ->onDelete('cascade');

            $table->foreign('user_id', 'FK_messages_users')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        DB::statement("ALTER TABLE messages COMMENT = 'Сообщения'");
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
