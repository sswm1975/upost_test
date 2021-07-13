<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id')->comment('Код');
            $table->integer('user_id')->comment('Пользователь');
            $table->integer('job_id')->comment('Задание');
            $table->decimal('amount', 10)->default(0.00)->comment('Сумма');
            $table->string('description', 255)->nullable()->comment('Описание');
            $table->string('status', 20)->nullable()->comment('Статус');
            $table->json('response')->nullable();
            $table->timestamp('created_at')->nullable()->comment('Дата добавления');
            $table->timestamp('updated_at')->nullable()->comment('Дата обновления');
            $table->timestamp('payed_at')->nullable()->comment('Дата оплаты');
        });

        DB::statement("ALTER TABLE transactions COMMENT = 'Транзакции по оплате'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
