<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_logs', function (Blueprint $table) {
            $table->id()->comment('Код');
            $table->unsignedInteger('user_id')->index()->comment('Користувач');
            $table->string('method', 50)->comment('API-метод');
            $table->json('in_params')->nullable()->comment('Вхідны параметри');
            $table->json('response')->nullable()->comment('Відповідь від Stripe');
            $table->unsignedTinyInteger('is_error')->default(0)->comment('Це помилка?');
            $table->timestamp('created_at')->nullable()->comment('Створено');
        });

        DB::statement("ALTER TABLE stripe_logs COMMENT = 'Логування Stripe запитів'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stripe_logs');
    }
}
