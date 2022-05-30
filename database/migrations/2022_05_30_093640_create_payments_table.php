<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->unsignedInteger('user_id')->comment('Пользователь');
            $table->decimal('amount', 10)->default(0.00)->comment('Сумма');
            $table->string('description')->comment('Описание');
            $table->unsignedInteger('admin_user_id')->nullable()->comment('Менеджер, выполнивший платеж');
            $table->unsignedInteger('transaction_id')->nullable()->comment('Транзакция по обработке платежа');
            $table->enum('status', ['active', 'rejected', 'done'])->default('active')->comment('Статус');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
        });

        DB::statement("ALTER TABLE payments COMMENT = 'Заявления на выплату платежа'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
