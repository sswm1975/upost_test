<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Код');
            $table->timestamp('time')->comment('Дата и время запроса');
            $table->decimal('duration', 10, 3)->comment('Продолжительность от запуска Laravel до отдачи ответа (в секундах)');
            $table->decimal('duration_request', 10, 3)->comment('Продолжительность от формирования запроса до отдачи ответа (в секундах)');
            $table->string('ip',50)->nullable()->comment('IP-адрес');
            $table->string('url')->nullable()->comment('Ссылка');
            $table->enum('method',['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE'])->nullable()->comment('Метод');
            $table->json('input')->nullable()->comment('Параметры запроса');
            $table->json('server')->nullable()->comment('Серверные переменные');
        });

        DB::statement("ALTER TABLE logs COMMENT = 'Журнал API запросов'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
