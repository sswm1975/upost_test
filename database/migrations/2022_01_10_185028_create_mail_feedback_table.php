<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_feedback', function (Blueprint $table) {
            $table->increments('id')->comment('Код');
            $table->string('subject', 30)->comment('Тема (раздел)');
            $table->string('name', 100)->comment('Имя клиента');
            $table->string('phone', 20)->comment('Телефон клиента');
            $table->string('email', 100)->comment('Email клиента');
            $table->text('text')->comment('Текст сообщения');
            $table->timestamp('created_at')->nullable()->comment('Дата добавления');
            $table->timestamp('updated_at')->nullable()->comment('Дата обновления');
            $table->timestamp('read_at')->nullable()->comment('Дата прочтения');
        });

        DB::statement("ALTER TABLE mail_feedback COMMENT = 'Обратная связь с клиентами'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_feedback');
    }
}
