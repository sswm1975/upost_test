<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWiseEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wise_events', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->enum('event_type', ['transfer-state-change', 'transfer-payout-failure'])->nullable()->comment('Тип події');
            $table->unsignedInteger('transfer_id')->nullable()->comment('Код трансферу');
            $table->unsignedInteger('profile_id')->nullable()->comment('Код профілю');
            $table->unsignedInteger('account_id')->nullable()->comment('Код рахунку');
            $table->string('state')->nullable()->comment('Стан');
            $table->json('event')->comment('Подія');
            $table->enum('status', ['new', 'skipped', 'processed'])->default('new')->comment('Статус обробки');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->default(DB::raw('NULL ON UPDATE CURRENT_TIMESTAMP'))->comment('Оновлено');
        });
        DB::statement("ALTER TABLE stripe_logs COMMENT = 'Wise події'");

        Schema::create('wise_resources', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->bigInteger('wise_event_id')->comment('Код події')->index();
            $table->enum('type', ['transfer', 'profile', 'account'])->nullable()->comment('Тип ресурсу (Трансфер/Профіль/Рахунок)');
            $table->unsignedInteger('resource_id')->comment('Код ресурсу');
            $table->json('resource')->comment('JSON ресурсу');
            $table->string('key', 50)->nullable()->comment('Головний ключ з ресурсу');
            $table->string('value')->nullable()->comment('Значення ключа з ресурсу');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Добавлено');
            $table->timestamp('updated_at')->nullable()->default(DB::raw('NULL ON UPDATE CURRENT_TIMESTAMP'))->comment('Оновлено');
        });
        DB::statement("ALTER TABLE stripe_logs COMMENT = 'Wise ресурси'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wise_events');
        Schema::dropIfExists('wise_resources');
    }
}
