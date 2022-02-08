<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyReviewsRenameJobIdToRateId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['reviewable_id', 'reviewable_type']);
            $table->renameColumn('rating', 'scores')->comment('Оценка')->change();
            $table->renameColumn('type', 'user_type')->comment('Тип автора')->change();
            $table->renameColumn('to_user_id', 'recipient_id')->comment('Получатель отзыва')->change();
            $table->renameColumn('job_id', 'rate_id')->comment('Код ставки')->change();
            $table->renameColumn('comment', 'text')->comment('Текст отзыва')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            //
        });
    }
}
