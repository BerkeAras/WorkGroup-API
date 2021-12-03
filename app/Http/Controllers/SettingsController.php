<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    // Returns the settings
    public function getSettings(Request $request) {

        $defaultConfig = DB::table('app_settings')
            ->where('config_key','app.name')
            ->orWhere('config_key','app.logo')
            ->orWhere('config_key','app.locale')
            ->orWhere('config_key','app.url')
            ->orWhere('config_key','app.registration_enabled')
            ->orWhere('config_key','app.password_reset_enabled')
            ->orWhere('config_key','server.api_url')
            ->orWhere('config_key','analytics.google_analytics.enabled')
            ->orWhere('config_key','analytics.google_analytics.key')
            ->orWhere('config_key','analytics.sentry.enabled')
            ->orWhere('config_key','analytics.sentry.dsn')
            ->get();

        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (JWTAuth::parseToken()->authenticate()) {
                $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
    
                $user = DB::table('users')->where('id', $user_id)->first();
                $userAdmin = $user->is_admin;
    
                if ($userAdmin == 1) {
                    $settings = DB::table('app_settings')->get();
                    return new JsonResponse($settings);
                } else {
                    
                    return new JsonResponse($defaultConfig);
    
                }
            } else {
                
                return new JsonResponse($defaultConfig);
    
            }
        } catch (JWTException $e) {
            return new JsonResponse($defaultConfig);
        }
        

    }

    // Save Settings
    public function saveSettings(Request $request) {

        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (JWTAuth::parseToken()->authenticate()) {
                $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
    
                $user = DB::table('users')->where('id', $user_id)->first();
                $userAdmin = $user->is_admin;
    
                if ($userAdmin == 1) {
                    
                    $settings = DB::table('app_settings')->get();

                    $totalAffectedRows = 0;

                    foreach ($request->all() as $requestInputKey => $requestInputValue) {
                        $affected = 0;
                        $affected = DB::table('app_settings')
                            ->where('config_key', $requestInputKey)
                            ->update(['config_value' => $requestInputValue]);
                        $totalAffectedRows = $totalAffectedRows + $affected;

                        if ($requestInputKey == "server.database.host") {
                            $this->changeEnvironmentVariable("DB_HOST", $requestInputValue);
                        }
                        if ($requestInputKey == "server.database.port") {
                            $this->changeEnvironmentVariable("DB_PORT", $requestInputValue);
                        }
                        if ($requestInputKey == "server.database.name") {
                            $this->changeEnvironmentVariable("DB_DATABASE", $requestInputValue);
                        }
                        if ($requestInputKey == "server.database.username") {
                            $this->changeEnvironmentVariable("DB_USERNAME", $requestInputValue);
                        }
                        if ($requestInputKey == "server.database.password") {
                            $this->changeEnvironmentVariable("DB_PASSWORD", $requestInputValue);
                        }
                        if ($requestInputKey == "app.url") {
                            $this->changeEnvironmentVariable("APP_URL", $requestInputValue);
                        }
                        if ($requestInputKey == "server.smtp.host") {
                            $this->changeEnvironmentVariable("MAIL_HOST", $requestInputValue);
                        }
                        if ($requestInputKey == "server.smtp.port") {
                            $this->changeEnvironmentVariable("MAIL_PORT", $requestInputValue);
                        }
                        if ($requestInputKey == "server.smtp.username") {
                            $this->changeEnvironmentVariable("MAIL_USERNAME", $requestInputValue);
                        }
                        if ($requestInputKey == "server.smtp.password") {
                            $this->changeEnvironmentVariable("MAIL_PASSWORD", $requestInputValue);
                        }
                        if ($requestInputKey == "server.smtp.encryption") {
                            $this->changeEnvironmentVariable("MAIL_ENCRYPTION", $requestInputValue);
                        }
                        if ($requestInputKey == "server.smtp.from_address") {
                            $this->changeEnvironmentVariable("MAIL_FROM_ADDRESS", $requestInputValue);
                        }
                        if ($requestInputKey == "server.smtp.from_name") {
                            $this->changeEnvironmentVariable("MAIL_FROM_NAME", $requestInputValue);
                        }

                    }

                    return new JsonResponse(array('status' => '1', 'affected_rows' => $totalAffectedRows));

                } else {
                    return new JsonResponse(["error" => "You are not authorized to perform this action."]);
                }
            } else {
                return new JsonResponse(["message" => "You are not authorized to perform this action."], 401);
            }
        } catch (JWTException $e) {
            return new JsonResponse(["message" => "You are not authorized to perform this action."], 401);
        }

    }

    // Upload Logo
    public function uploadLogo(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $user = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $user->is_admin;

            if ($userAdmin == 1) {
                
                $response = array();
                $upload_dir = './static/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if ($_FILES['logoFile']) {
                    $logo_name = $_FILES["logoFile"]["name"];
                    $logo_tmp_name = $_FILES["logoFile"]["tmp_name"];
                    $error = $_FILES["logoFile"]["error"];

                    if($error > 0){
                        $response = array(
                            "status" => "error",
                            "error" => true,
                            "message" => "Error uploading the file!"
                        );
                    } else {
                        $random_name = "logo_" . rand(1000,1000000) . time() . "-" . $logo_name;
                        $upload_name = $upload_dir . strtolower($random_name);
                        $upload_name = preg_replace('/\s+/', '-', $upload_name);

                        if (move_uploaded_file($logo_tmp_name , $upload_name)) {

                            DB::table('app_settings')
                                ->where('config_key', "app.logo")
                                ->update(['config_value' => str_replace("./static/","",$upload_name)]);

                            $response = array(
                                "status" => "1",
                                "error" => false,
                                "message" => "File uploaded successfully",
                                "url" => $upload_name
                            );
                        } else {
                            $response = array(
                                "status" => "0",
                                "error" => true,
                                "message" => "Error uploading the file!"
                            );
                        }
                    }    

                } else {
                    $response = array(
                        "status" => "0",
                        "error" => true,
                        "message" => "No file was sent!"
                    );
                }

                return new JsonResponse($response);

            } else {
                return new JsonResponse(["message" => "You are not authorized to access this resource."], 401);
            }
        } else {
            return new JsonResponse(["message" => "You are not authorized to access this resource."], 401);
        }
    }

    // Update Environment Variables
    public static function changeEnvironmentVariable($key,$value) {
        $path = base_path('.env');

        if(is_bool(env($key)))
        {
            $old = env($key)? 'true' : 'false';
        }
        elseif(env($key)===null){
            $old = 'null';
        }
        else{
            $old = env($key);
        }

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                "$key=".$old, "$key=" . '"' .$value . '"', str_replace(
                    "$key=" . '"' .$old . '"', "$key=" . '"' .$value . '"', file_get_contents($path)
                )
            ));
        }
    }

    // Get Users
    public static function getUsers(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {

            if (isset($request->only('page')["page"])) {
                $page = $request->only('page')["page"];
            } else {
                $page = 1;
            }

	        $start_from = ($page-1) * 10;

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $user = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $user->is_admin;

            if ($userAdmin == 1) {
                $users = DB::table('users')
                    ->select('id', 'name', 'email', 'is_admin', 'created_at', 'updated_at', 'avatar')
                    ->orderBy('id', 'desc')
                    ->skip($start_from)
                    ->take(10)
                    ->get();

                $total_records = DB::table('users')->count();
                $total_pages = ceil($total_records / 10);

                $response = array(
                    "status" => "1",
                    "error" => false,
                    "message" => "Users fetched successfully",
                    "users" => $users,
                    "total_pages" => $total_pages
                );
            } else {
                $response = array(
                    "status" => "0",
                    "error" => true,
                    "message" => "You are not authorized to perform this action."
                );
            }

            return new JsonResponse($response);
        
        }
    }
}
