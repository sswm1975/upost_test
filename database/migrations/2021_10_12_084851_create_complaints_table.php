<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->tinyIncrements('id')->comment('Код');
            $table->string('name_uk', 50)->comment('Наименование на украинском');
            $table->string('name_ru', 50)->comment('Наименование на русском');
            $table->string('name_en', 50)->comment('Наименование на английском');
        });

        DB::statement("ALTER TABLE complaints COMMENT = 'Типы нарушений/жалоб'");

        DB::table('complaints')->insert([
            ['name_uk' => 'Неприпустимий контент', 'name_ru' => 'Недопустимый контент', 'name_en' => 'Invalid content'],
            ['name_uk' => 'Неправильне опис', 'name_ru' => 'Неправильное описание', 'name_en' => 'Wrong description'],
            ['name_uk' => 'Неправильна ціна', 'name_ru' => 'Неправильная цена', 'name_en' => 'Wrong price'],
            ['name_uk' => 'Рекламний контент', 'name_ru' => 'Рекламный контент', 'name_en' => 'Advertising content'],
            ['name_uk' => 'Шахрайство', 'name_ru' => 'Мошенничество', 'name_en' => 'Fraud'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('complaints');
    }
}
