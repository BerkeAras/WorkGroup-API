<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Posts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('group_id');
            $table->longText('post_content')->nullable();
            $table->integer('status');
            $table->timestamps();
        });

        Schema::create('post_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('post_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->integer('user_id');
            $table->integer('parent_id');
            $table->longText('comment_content')->nullable();
            $table->timestamps();
        });
       
        Schema::create('post_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('post_image_url');
            $table->timestamps();
        });
       
        Schema::create('post_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('post_file_original');
            $table->string('post_file_url');
            $table->timestamps();
        });
       
        Schema::create('post_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->integer('user_id');
            $table->string('report_reason');
            $table->longText('report_text')->nullable();
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
        Schema::drop('posts');
        Schema::drop('post_likes');
        Schema::drop('post_comments');
        Schema::drop('post_images');
        Schema::drop('post_files');
    }
}
