<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaitRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wait_ranges', function (Blueprint $table) {
            $table->tinyIncrements('id')->comment('Код');
            $table->unsignedTinyInteger('order')->default(0)->comment('Порядок');
            $table->string('name_uk', 20)->comment('Наименование на украинском');
            $table->string('name_ru', 20)->comment('Наименование на русском');
            $table->string('name_en', 20)->comment('Наименование на английском');
            $table->unsignedTinyInteger('days')->comment('Количество дней');
            $table->boolean('active')->default(1)->comment('Действует (да/нет)');
        });

        DB::statement("ALTER TABLE wait_ranges COMMENT = 'Диапазоны ожидания'");

        DB::table('wait_ranges')->insert([
            ['order' => 1, 'name_uk' => 'Тиждень',    'name_ru' => 'Неделя',     'name_en' => 'A week',       'days' => 7],
            ['order' => 2, 'name_uk' => 'Два тижні',  'name_ru' => 'Две недели', 'name_en' => 'Two weeks',    'days' => 14],
            ['order' => 3, 'name_uk' => 'Три тижні',  'name_ru' => 'Три недели', 'name_en' => 'Three weeks',  'days' => 21],
            ['order' => 4, 'name_uk' => 'Місяць',     'name_ru' => 'Месяц',      'name_en' => 'Month',        'days' => 30],
            ['order' => 5, 'name_uk' => 'Два місяці', 'name_ru' => 'Два месяца', 'name_en' => 'Two months',   'days' => 60],
            ['order' => 6, 'name_uk' => 'Три місяці', 'name_ru' => 'Три месяца', 'name_en' => 'Three months', 'days' => 90],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wait_ranges');
    }
}
