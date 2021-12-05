<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // New Folder
        DB::table('knowledge_base_folders')->insert([
            'knowledge_base_folder_name' => 'First Folder',
            'knowledge_base_folder_slug' => 'first-folder',
            'knowledge_base_folder_description' => 'This is the first folder',
            'knowledge_base_folder_parent_id' => 0,
            'knowledge_base_folder_user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Permissions
        /*
            knowledge_base_permission_user_id
            knowledge_base_permission_folder_id
            knowledge_base_permission_write
            knowledge_base_permission_delete
        */
        DB::table('knowledge_base_permissions')->insert([
            'knowledge_base_permission_user_id' => 1,
            'knowledge_base_permission_folder_id' => 1,
            'knowledge_base_permission_write' => 1,
            'knowledge_base_permission_delete' => 1,
            'knowledge_base_permission_modify' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Files
        /*
            knowledge_base_file_name
            knowledge_base_file_slug
            knowledge_base_file_description
            knowledge_base_file_folder_id
            knowledge_base_file_user_id
        */
        DB::table('knowledge_base_files')->insert([
            'knowledge_base_file_name' => 'First File',
            'knowledge_base_file_slug' => 'first-file',
            'knowledge_base_file_description' => 'This is the first file',
            'knowledge_base_file_extension' => 'txt',
            'knowledge_base_file_path' => '1638617738-first-file.txt',
            'knowledge_base_file_folder_id' => 1,
            'knowledge_base_file_user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // File Comments
        /*
            knowledge_base_file_comment_user_id
            knowledge_base_file_comment_file_id
            knowledge_base_file_comment_content
        */
        DB::table('knowledge_base_file_comments')->insert([
            'knowledge_base_file_comment_user_id' => 1,
            'knowledge_base_file_comment_file_id' => 1,
            'knowledge_base_file_comment_content' => 'This is the first file comment',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // File Activity
        /*
            knowledge_base_file_activity_user_id
            knowledge_base_file_activity_file_id
            knowledge_base_file_activity_action
        */
        DB::table('knowledge_base_file_activity')->insert([
            'knowledge_base_file_activity_user_id' => 1,
            'knowledge_base_file_activity_file_id' => 1,
            'knowledge_base_file_activity_action' => 'created',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Folder Activity
        /*
            knowledge_base_folder_activity_user_id
            knowledge_base_folder_activity_folder_id
            knowledge_base_folder_activity_action
        */
        DB::table('knowledge_base_folder_activity')->insert([
            'knowledge_base_folder_activity_user_id' => 1,
            'knowledge_base_folder_activity_folder_id' => 1,
            'knowledge_base_folder_activity_action' => 'created',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Folder Comments
        /*
            knowledge_base_folder_comment_user_id
            knowledge_base_folder_comment_folder_id
            knowledge_base_folder_comment_content
        */
        DB::table('knowledge_base_folder_comments')->insert([
            'knowledge_base_folder_comment_user_id' => 1,
            'knowledge_base_folder_comment_folder_id' => 1,
            'knowledge_base_folder_comment_content' => 'This is the first folder comment',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
