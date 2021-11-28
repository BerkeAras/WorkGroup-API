<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user_id = DB::table('users')->insertGetId([
            'name' => 'WorkGroup Administrator',
            'email' => 'admin@workgroup.com',
            'password' => app('hash')->make('password'),
            'remember_token' => str_random(10),
            'is_admin' => true,
        ]);

        DB::table('user_information')->insert([
            'user_id' => $user_id,
            'user_slogan' => 'Test-Account',
            'user_country' => 'US',
            'user_city' => 'New York',
            'user_department' => 'Example Department',
            'user_birthday' => '2000-01-01',
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
    }
}
