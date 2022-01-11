<?php

use App\Models\Feedback;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->increments('id')->comment('Код');
            $table->enum('subject',  array_keys(Feedback::SUBJECT_TYPES))->default(Feedback::SUBJECT_WITHOUT_NAME)->comment('Тема (раздел)');
            $table->string('name', 100)->comment('Имя клиента');
            $table->string('phone', 20)->comment('Телефон клиента');
            $table->string('email', 100)->comment('Email клиента');
            $table->text('text')->comment('Текст сообщения');
            $table->timestamp('created_at')->nullable()->comment('Дата добавления');
            $table->timestamp('updated_at')->nullable()->comment('Дата обновления');
            $table->timestamp('read_at')->nullable()->comment('Дата прочтения');
            $table->integer('read_user_id')->nullable()->comment('Пользователь, который прочитал письмо');
        });

        DB::statement("ALTER TABLE feedback COMMENT = 'Обратная связь с клиентами'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feedback');
    }
}
