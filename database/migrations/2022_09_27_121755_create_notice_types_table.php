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
            $table->string('title', 50)->comment('Наименование');
            $table->string('name_uk', 50)->comment('Текст уведомления на украинском');
            $table->string('name_ru', 50)->comment('Текст уведомления на русском');
            $table->string('name_en', 50)->comment('Текст уведомления на английском');
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
