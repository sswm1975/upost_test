<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyDisputesTableAddReason extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->unsignedTinyInteger('dispute_closed_reason_id')
                ->nullable()
                ->comment('Причина закрытия спора');

            $table->foreign('dispute_closed_reason_id', 'FK_disputes_dispute_closed_reason')
                ->references('id')
                ->on('dispute_closed_reasons')
                ->onDelete('cascade');

            $table->text('reason_closing_description')
                ->nullable()
                ->comment('Детальное описание закрытия спора менеджером чата');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn('dispute_closed_reasons_id');
            $table->dropColumn('reason_closing_description');
        });
    }
}
