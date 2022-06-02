<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->integerIncrements('id')->comment('Код');
            $table->string('ttn', 20)->comment('ТТН');
            $table->unsignedInteger('dispute_id')->nullable()->comment('Код спора');
            $table->enum('status', ['new', 'sent', 'received', 'verified', 'failed', 'closed'])->comment('Статус');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
        });
        DB::statement("ALTER TABLE tracks COMMENT = 'Треки доставки'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tracks');
    }
}
