<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_deductions', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->unsignedInteger('order_id')->comment('Код заказа');
            $table->enum('type', ['tax_export', 'tax_import', 'fee'])->comment('Тип вычета: tax_export - экспортный налог, tax_import - налог на импорт, fee - комиссии');
            $table->string('name', 50)->comment('Наименование вычета');
            $table->decimal('amount', 10)->default(0.00)->comment('Сумма (в долларах)');
            $table->timestamp('created_at')->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');

            $table->foreign('order_id', 'FK_order_deductions_orders')
                ->references('id')->on('orders')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement("ALTER TABLE order_deductions COMMENT = 'Вычеты, удержания по заказу'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_deductions');
    }
}
