<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProblemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->tinyIncrements('id')->comment('Код');
            $table->string('name_uk', 20)->comment('Наименование на украинском');
            $table->string('name_ru', 20)->comment('Наименование на русском');
            $table->string('name_en', 20)->comment('Наименование на английском');
            $table->unsignedTinyInteger('days')->comment('Количество дней');
            $table->boolean('active')->default(1)->comment('Действует (да/нет)');
        });

        DB::statement("ALTER TABLE problems COMMENT = 'Проблемы спора'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('problems');
    }
}
