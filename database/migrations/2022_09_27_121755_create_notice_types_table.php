<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticeTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice_types', function (Blueprint $table) {
            $table->string('id', 50)->comment('Код')->primary();
            $table->string('name', 50)->comment('Наименование');
            $table->enum('mode', ['scheduler', 'event', 'manually'])->comment('Режим работы');
            $table->string('text_uk', 100)->comment('Текст уведомления на украинском');
            $table->string('text_ru', 100)->comment('Текст уведомления на русском');
            $table->string('text_en', 100)->comment('Текст уведомления на английском');
            $table->boolean('active')->default(1)->comment('Действует (да/нет)');
            $table->text('description')->nullable()->comment('Описание');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
        });

        DB::statement("ALTER TABLE notice_types COMMENT = 'Типы уведомлений'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notice_types');
    }
}
