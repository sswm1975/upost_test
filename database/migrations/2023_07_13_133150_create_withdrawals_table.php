<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->unsignedInteger('user_id')->index()->comment('Користувач');
            $table->decimal('amount', 10)->default(0.00)->comment('Сума');
            $table->string('email')->comment('Електрона адреса');
            $table->enum('status', ['new', 'in_progress', 'fail', 'expired', 'done'])->default('new')->comment('Статус');
            $table->timestamp('created_at')->nullable()->comment('Створено');
            $table->timestamp('updated_at')->nullable()->comment('Змінено');
        });

        DB::statement("ALTER TABLE withdrawals COMMENT = 'Заявки на вивід грошей'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdrawals');
    }
}
