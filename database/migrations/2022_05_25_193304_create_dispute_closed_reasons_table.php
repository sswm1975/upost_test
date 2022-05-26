<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisputeClosedReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispute_closed_reasons', function (Blueprint $table) {
            $table->tinyIncrements('id')->comment('Код');
            $table->string('name', 100)->comment('Наименование');
            $table->string('alias', 100)->comment('Алиас');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
        });

        DB::statement("ALTER TABLE dispute_closed_reasons COMMENT = 'Причины закрытия спора'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dispute_closed_reasons');
    }
}
