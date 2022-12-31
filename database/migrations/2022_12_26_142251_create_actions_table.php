<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Код');
            $table->unsignedInteger('user_id')->comment('Код пользователя (инициатора или участника действия)');
            $table->unsignedTinyInteger('is_owner')->comment('Пользователь является инициатором действия?');
            $table->string('name', 50)->comment('Наименование действия/события');
            $table->json('data')->nullable()->comment('Данные');
            $table->timestamp('created_at')->comment('Добавлено');
        });

        DB::statement("ALTER TABLE actions COMMENT = 'Действия/события пользователя'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actions');
    }
}
