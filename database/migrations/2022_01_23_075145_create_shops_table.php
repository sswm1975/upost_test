<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->tinyIncrements('id')->comment('Код');
            $table->string('name', 50)->comment('Наименование');
            $table->string('slug', 20)->unique()->comment('Слаг');
            $table->string('url')->nullable()->comment('Ссылка');
            $table->unsignedTinyInteger('active')->default('1')->comment('Активный');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
        });

        DB::statement("ALTER TABLE shops COMMENT = 'Интернет-магазины'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
