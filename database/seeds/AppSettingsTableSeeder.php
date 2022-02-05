<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $adminId = 0;

        $user = DB::table('users')->where('name', 'WorkGroup Administrator')->first();
        $adminId = $user->id;

        DB::table('app_settings')->insert([
            'config_key' => 'app.name',
            'config_value' => 'WorkGroup',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'app.logo',
            'config_value' => 'default_logo.svg',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'app.locale',
            'config_value' => 'en',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'app.url',
            'config_value' => 'http://localhost:3000',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'app.registration_enabled',
            'config_value' => 'true',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'app.password_reset_enabled',
            'config_value' => 'true',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'app.minimum_search_length',
            'config_value' => 3,
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'app.maximum_posts_per_page',
            'config_value' => 10,
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        DB::table('app_settings')->insert([
            'config_key' => 'server.api_url',
            'config_value' => 'https://api.workgroup.com',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        DB::table('app_settings')->insert([
            'config_key' => 'server.database.host',
            'config_value' => 'localhost',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.database.port',
            'config_value' => '3306',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.database.username',
            'config_value' => 'root',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.database.password',
            'config_value' => 'root',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.database.name',
            'config_value' => 'workgroup',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);

        DB::table('app_settings')->insert([
            'config_key' => 'server.smtp.host',
            'config_value' => 'localhost',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.smtp.port',
            'config_value' => '465',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.smtp.encryption',
            'config_value' => 'ssl',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.smtp.username',
            'config_value' => 'workgroup',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.smtp.password',
            'config_value' => 'password',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.smtp.from_address',
            'config_value' => 'noreply@workgroup.com',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'server.smtp.from_name',
            'config_value' => 'WorkGroup System',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('app_settings')->insert([
            'config_key' => 'analytics.google_analytics.enabled',
            'config_value' => 'true',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'analytics.google_analytics.key',
            'config_value' => 'UA-XXXXXXXXX-X',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('app_settings')->insert([
            'config_key' => 'analytics.sentry.enabled',
            'config_value' => 'true',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'analytics.sentry.dsn',
            'config_value' => 'https://XXXXXXXXX.XXXXXXXXX.sentry.io/XXXXXXXXX',
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('app_settings')->insert([
            'config_key' => 'other.avatar_quality',
            'config_value' => 'min', // min, medium, max
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'other.banner_quality',
            'config_value' => 'min', // min, medium, max
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
        DB::table('app_settings')->insert([
            'config_key' => 'other.post_image_quality',
            'config_value' => 'min', // min, medium, max
            'last_changed_by' => $adminId,
            "created_at" =>  date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
    }
}
