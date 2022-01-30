<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('groups')->insertGetId([
            'creator_user_id' => '2',
            'group_title' => 'First Group',
            'group_description' => 'This is the first (public) #group!',
            'group_avatar' => '',
            'group_banner' => '',
            'created_at' =>  date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('group_members')->insert([
            'group_id' => '1',
            'user_id' => '1',
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('group_members')->insert([
            'group_id' => '1',
            'user_id' => '2',
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('group_tags')->insert([
            'group_id' => '1',
            'tag' => 'group',
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
    }
}
