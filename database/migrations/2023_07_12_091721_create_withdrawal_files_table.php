<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawalFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawal_files', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->string('name')->nullable()->comment('Найменування файлу');
            $table->timestamp('created_at')->useCurrent()->comment('Створено');
        });

        DB::statement("ALTER TABLE withdrawal_files COMMENT = 'Файли по заявкам на вивід грошей'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdrawal_files');
    }
}
