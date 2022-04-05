<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('disputes');

        Schema::create('disputes', function (Blueprint $table) {
            $table->increments('id')->comment('Код');
            $table->unsignedTinyInteger('problem_id')->comment('Код проблема');
            $table->unsignedInteger('user_id')->comment('Код пользователя');
            $table->unsignedInteger('rate_id')->comment('Код ставки');
            $table->unsignedBigInteger('chat_id')->comment('Код чата');
            $table->unsignedBigInteger('message_id')->comment('Код сообщения');
            $table->enum('status', ['active', 'in_work', 'closed'])->default('active')->comment('Статус спора');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
            $table->timestamp('deadline')->nullable()->comment('Дедлайн');
            $table->unsignedInteger('closed_user_id')->nullable()->comment('Код пользователя закрывший спор');

            $table->foreign('problem_id', 'FK_disputes_problems')->references('id')->on('problems')->onDelete('cascade');
            $table->foreign('user_id', 'FK_disputes_users')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rate_id', 'FK_disputes_rates')->references('id')->on('rates')->onDelete('cascade');
            $table->foreign('chat_id', 'FK_disputes_chats')->references('id')->on('chats')->onDelete('cascade');
            $table->foreign('message_id', 'FK_disputes_messages')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('closed_user_id', 'FK_disputes_closed_users')->references('id')->on('users')->onDelete('cascade');
        });

        DB::statement("ALTER TABLE disputes COMMENT = 'Споры'");
   }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disputes');
    }
}
