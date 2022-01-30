<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Notifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('notification_recipient_user_id')->unsigned();
            $table->integer('notification_sender_user_id')->unsigned();
            $table->text('notification_subject');
            $table->text('notification_content');
            $table->text('notification_link');
            $table->text('notification_type');
            $table->enum('notification_delivery_type',array('email', 'inapp'))->default('email');
            $table->boolean('notification_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notifications');
    }
}
