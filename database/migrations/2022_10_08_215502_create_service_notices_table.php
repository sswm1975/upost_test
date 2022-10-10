<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_notices', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->string('name', 50)->comment('Наименование');
            $table->string('text_uk', 100)->comment('Текст уведомления на украинском');
            $table->string('text_ru', 100)->comment('Текст уведомления на русском');
            $table->string('text_en', 100)->comment('Текст уведомления на английском');
            $table->unsignedInteger('admin_user_id')->nullable()->comment('Администратор, который отправил уведомление');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
            $table->timestamp('sent_at')->nullable()->comment('Отправлено');
        });

        DB::statement("ALTER TABLE service_notices COMMENT = 'Системные уведомления'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_notices');
    }
}
