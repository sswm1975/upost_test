<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrencyRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('currencies');

        Schema::create('currencies', function (Blueprint $table) {
            $table->char('id', 1)->comment('Код')->primary();
            $table->char('symbol', 3)->comment('3-х буквенный код валюты');
            $table->unsignedSmallInteger('code')->comment('Код валюты');
            $table->decimal('rate',12, 6)->default(0)->comment('Курс');
            $table->timestamp('created_at')->nullable()->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->comment('Изменено');
        });
        DB::statement("ALTER TABLE currencies COMMENT = 'Валюты'");

        $now = date('Y-m-d H:i:s');
        DB::table('currencies')->insert([
            ['id' => '$', 'symbol' => 'USD', 'code' => 840, 'rate' => 1, 'created_at' => $now],
            ['id' => '₴', 'symbol' => 'UAH', 'code' => 980, 'rate' => 0, 'created_at' => $now],
            ['id' => '€', 'symbol' => 'EUR', 'code' => 978, 'rate' => 0, 'created_at' => $now],
            ['id' => '₽', 'symbol' => 'RUB', 'code' => 643, 'rate' => 0, 'created_at' => $now],
        ]);

        Schema::create('currency_rates', function (Blueprint $table) {
            $table->integerIncrements('id')->comment('Код');
            $table->date('date')->comment('Дата')->index();
            $table->char('currency_id', 1)->comment('Валюта');
            $table->decimal('rate',12, 6)->comment('Курс');
            $table->timestamp('created_at')->comment('Добавлено');

            $table->foreign('currency_id', 'FK_currency_rates_currencies')
                ->references('id')->on('currencies')
                ->onDelete('restrict')->onUpdate('restrict');
        });
        DB::statement("ALTER TABLE currency_rates COMMENT = 'История курсов валют'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('currencies');
    }
}
