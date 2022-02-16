<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

class KnowledgeBase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // KnowledgeBase
        Schema::create('knowledge_base_folders', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->string('knowledge_base_folder_name', 100);
            $table->string('knowledge_base_folder_slug', 100);
            $table->string('knowledge_base_folder_description', 500)->nullable();
            $table->integer('knowledge_base_folder_parent_id')->unsigned()->nullable();
            $table->integer('knowledge_base_folder_user_id')->unsigned();
            $table->boolean('knowledge_base_folder_status')->default(1);
            $table->timestamps();
        });

        // KnowledgeBase User Permissions
        Schema::create('knowledge_base_permissions', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('knowledge_base_permission_user_id');
            $table->integer('knowledge_base_permission_folder_id')->unsigned();
            $table->boolean('knowledge_base_permission_read')->default(0);
            $table->boolean('knowledge_base_permission_write')->default(0);
            $table->boolean('knowledge_base_permission_delete')->default(0);
            $table->boolean('knowledge_base_permission_modify')->default(0);
            $table->timestamps();
        });

        // KnowledgeBase Files
        Schema::create('knowledge_base_files', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->string('knowledge_base_file_name', 100);
            $table->string('knowledge_base_file_slug', 100);
            $table->string('knowledge_base_file_extension', 100)->nullable();
            $table->string('knowledge_base_file_description', 500)->nullable();
            $table->string('knowledge_base_file_path')->unique();
            $table->integer('knowledge_base_file_folder_id')->unsigned();
            $table->integer('knowledge_base_file_user_id')->unsigned();
            $table->boolean('knowledge_base_file_status')->default(1);
            $table->timestamps();
        });

        // KnowledgeBase File Comments
        Schema::create('knowledge_base_file_comments', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('knowledge_base_file_comment_user_id')->unsigned();
            $table->integer('knowledge_base_file_comment_file_id')->unsigned();
            $table->text('knowledge_base_file_comment_content');
            $table->timestamps();
        });

        // KnowledgeBase File Activity
        Schema::create('knowledge_base_file_activity', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('knowledge_base_file_activity_user_id')->unsigned();
            $table->integer('knowledge_base_file_activity_file_id')->unsigned();
            $table->string('knowledge_base_file_activity_action', 100);
            $table->timestamps();
        });

        // KnowledgeBase File History
        Schema::create('knowledge_base_file_history', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('knowledge_base_file_history_user_id')->unsigned();
            $table->integer('knowledge_base_file_history_id')->unsigned();
            $table->string('knowledge_base_file_history_path');
            $table->timestamps();
        });

        // KnowledgeBase Folder Activity
        Schema::create('knowledge_base_folder_activity', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('knowledge_base_folder_activity_user_id')->unsigned();
            $table->integer('knowledge_base_folder_activity_folder_id')->unsigned();
            $table->string('knowledge_base_folder_activity_action', 100);
            $table->timestamps();
        });

        // KnowledgeBase Folder Comments
        Schema::create('knowledge_base_folder_comments', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('knowledge_base_folder_comment_user_id')->unsigned();
            $table->integer('knowledge_base_folder_comment_folder_id')->unsigned();
            $table->text('knowledge_base_folder_comment_content');
            $table->timestamps();
        });

        // KnowledgeBase File Downloads
        Schema::create('knowledge_base_file_downloads', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('knowledge_base_file_download_user_id')->unsigned();
            $table->integer('knowledge_base_file_download_file_id')->unsigned();
            $table->text('knowledge_base_file_download_token')->nullable();
            $table->boolean('knowledge_base_file_download_status')->default(0);
            $table->timestamp('knowledge_base_file_download_time')->nullable();
            $table->timestamps();
        });

        // Create the File
        $path = resource_path() . '/knowledge-base-data/0-index.md';
        File::put($path, '# Knowledge Base');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('knowledge_base_folders');
        Schema::drop('knowledge_base_permissions');
        Schema::drop('knowledge_base_files');
        Schema::drop('knowledge_base_file_comments');
        Schema::drop('knowledge_base_file_activity');
        Schema::drop('knowledge_base_folder_activity');
        Schema::drop('knowledge_base_folder_comments');
        Schema::drop('knowledge_base_file_downloads');
    }
}
