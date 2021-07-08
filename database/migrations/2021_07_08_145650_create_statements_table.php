<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStatementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statements', function (Blueprint $table) {
            $table->increments('id')->comment('Код');
            $table->unsignedInteger('user_id')->comment('Пользователь');
            $table->unsignedInteger('rate_id')->comment('Ставка');
            $table->enum('status', ['active', 'rejected', 'done'])->default('active')->comment('Статус');
            $table->timestamp('created_at')->nullable()->comment('Дата добавления');
            $table->timestamp('updated_at')->nullable()->comment('Дата обновления');
        });

        DB::statement("ALTER TABLE statements COMMENT = 'Заявления'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('statements');
    }
}
