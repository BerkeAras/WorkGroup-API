<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class Groups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('creator_user_id');
            $table->string('group_title');
            $table->string('group_description');
            $table->string('group_avatar');
            $table->string('group_banner');
            $table->integer('group_private')->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('group_members', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('group_id');
            $table->integer('user_id');
            $table->integer('is_admin')->default(0);
            $table->timestamps();
        });
        Schema::create('group_tags', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('group_id');
            $table->string('tag');
            $table->timestamps();
        });
        Schema::create('group_requests', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('group_id');
            $table->integer('user_id');
            $table->enum('status',array('pending','approved','rejected','cancelled'))->default('pending');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE groups ADD FULLTEXT fulltext_index(group_title, group_description)');
        DB::statement('ALTER TABLE group_tags ADD FULLTEXT fulltext_index(tag)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('groups');
        Schema::drop('group_members');
        Schema::drop('group_tags');
    }
}
