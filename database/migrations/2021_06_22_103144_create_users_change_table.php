<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersChangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_change', function (Blueprint $table) {
            $table->char('token', 8)->primary();
            $table->unsignedInteger('user_id');
            $table->string('user_email', 30)->nullable();
            $table->string('user_phone', 30)->nullable();
            $table->string('user_password', 32)->nullable();
            $table->char('user_card_number', 16)->nullable();
            $table->string('user_card_name', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE users_change COMMENT = "Запросы на смену данных пользователя"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_change');
    }
}
