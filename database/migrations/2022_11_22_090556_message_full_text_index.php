<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MessageFullTextIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE messages ADD FULLTEXT IDX_messages_text(text)');
        DB::statement('ALTER TABLE users ADD FULLTEXT IDX_users_names(name, surname)');
        DB::statement('ALTER TABLE orders ADD FULLTEXT IDX_orders_name(name)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE messages DROP INDEX IDX_messages_text');
        DB::statement('ALTER TABLE users DROP INDEX IDX_users_names');
        DB::statement('ALTER TABLE orders DROP INDEX IDX_orders_name');
    }
}
