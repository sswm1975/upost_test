<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCalculationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_calculations', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->unsignedInteger('order_id')->comment('Код заказа');
            $table->enum('type', ['tax_export', 'tax_import', 'fee'])->comment('Тип');
            $table->string('name', 50)->comment('Наименование');
            $table->decimal('amount', 10)->default(0.00)->comment('Сумма');
            $table->timestamp('created_at')->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');

            $table->foreign('order_id', 'FK_order_calculations_orders')
                ->references('id')->on('orders')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement("ALTER TABLE order_calculations COMMENT = 'Расчёты по заказу'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_calculations');
    }
}
