<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Код');
            $table->unsignedInteger('user_id')->comment('Код пользователя');
            $table->string('notice_type', 50)->comment('Тип уведомления');
            $table->unsignedTinyInteger('is_read')->default(0)->comment('Уведомление прочитано?');
            $table->integer('object_id')->nullable()->comment('Код объекта инициирующего событие');
            $table->json('data')->nullable()->comment('Данные');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');

            $table->foreign('user_id', 'FK_notices_users')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');

            $table->foreign('notice_type', 'FK_notices_notice_types')
                ->references('id')->on('notice_types')
                ->onDelete('restrict')->onUpdate('restrict');
        });

        DB::statement("ALTER TABLE notices COMMENT = 'Уведомления'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notices');
    }
}
