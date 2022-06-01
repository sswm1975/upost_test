<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScriptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scripts', function (Blueprint $table) {
            $table->tinyIncrements('id')->comment('Код');
            $table->string('name', 100)->comment('Наименование');
            $table->string('alias', 50)->comment('Алиас');
            $table->text('code')->comment('PHP-код');
            $table->text('description')->comment('Описание');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
        });
        DB::statement("ALTER TABLE scripts COMMENT = 'Скрипты'");

        Schema::create('script_from_country', function (Blueprint $table) {
            $table->unsignedTinyInteger('script_id')->comment('Код скрипта');
            $table->unsignedInteger('country_id')->comment('Код страны');
            $table->index(['script_id', 'country_id']);
        });
        DB::statement("ALTER TABLE script_from_country COMMENT = 'Использовать скрипт для страны Откуда'");

        Schema::create('script_to_country', function (Blueprint $table) {
            $table->unsignedTinyInteger('script_id')->comment('Код скрипта');
            $table->unsignedInteger('country_id')->comment('Код страны');
            $table->index(['script_id', 'country_id']);
        });
        DB::statement("ALTER TABLE script_to_country COMMENT = 'Использовать скрипт для страны Куда'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('script_from_country');
        Schema::dropIfExists('script_to_country');
        Schema::dropIfExists('scripts');
    }
}
