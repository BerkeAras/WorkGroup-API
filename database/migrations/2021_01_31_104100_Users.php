<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('avatar');
            $table->string('banner');
            $table->string('email',128)->unique();
            $table->string('password', 60);
            $table->string('cookie_choice');
            $table->boolean('user_online');
            $table->datetime('user_last_online');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('user_information', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id');
            $table->string('user_slogan');
            $table->string('user_country');
            $table->string('user_city');
            $table->string('user_street');
            $table->string('user_department');
            $table->string('user_birthday');
            $table->string('user_phone');
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
        Schema::drop('users');
        Schema::drop('user_information');
    }
}
