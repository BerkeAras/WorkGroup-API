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
            ->orWhere('config_key','app.group_creation_enabled')
            ->orWhere('config_key','app.minimum_search_length')
            ->orWhere('config_key','app.maximum_posts_per_page')
            ->orWhere('config_key','server.api_url')
            ->orWhere('config_key','analytics.google_analytics.enabled')
            ->orWhere('config_key','analytics.google_analytics.key')
            ->orWhere('config_key','analytics.sentry.enabled')
            ->orWhere('config_key','analytics.sentry.dsn')
            ->orWhere('config_key','other.avatar_quality')
            ->orWhere('config_key','other.banner_quality')
            ->orWhere('config_key','other.post_image_quality')
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

            if (isset($request->only("orderBy")["orderBy"])) {
                $orderBy = $request->only("orderBy")["orderBy"];
            } else {
                $orderBy = "created-at-desc";
            }

            // Allowed Orders
            $allowedOrders = array(
                'created-at-desc',
                'created-at-asc',
                'online-desc',
                'online-asc',
                'admin-desc',
                'admin-asc'
            );
            // Check if orderBy is allowed
            if (!in_array($orderBy, $allowedOrders)) {
                $response = array(
                    "status" => "invalid_orderby_parameter",
                    "error" => true,
                    "message" => "Invalid orderBy parameter",
                );
                return new JsonResponse($response);
            }

            // Convert orders to SQL
            $orderField = "created_at";
            $orderType = "desc";

            if($orderBy == "created-at-desc"){
                $orderField = "created_at";
                $orderType = "desc";
            }
            if($orderBy == "created-at-asc"){
                $orderField = "created_at";
                $orderType = "asc";
            }
            if($orderBy == "online-desc"){
                $orderField = "user_online";
                $orderType = "desc";
            }
            if($orderBy == "online-asc"){
                $orderField = "user_online";
                $orderType = "asc";
            }
            if($orderBy == "admin-desc"){
                $orderField = "is_admin";
                $orderType = "desc";
            }
            if($orderBy == "admin-asc"){
                $orderField = "is_admin";
                $orderType = "asc";
            }


	        $start_from = ($page-1) * 10;

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $user = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $user->is_admin;

            if ($userAdmin == 1) {

                if ($orderField == "user_online") {
                    $users = DB::table('users')
                        ->select('id', 'name', 'email', 'is_admin', 'created_at', 'updated_at', 'avatar', 'banner', 'cookie_choice', 'account_activated', 'activation_token', 'user_online', 'is_admin', 'user_last_online', 'user_last_ip', 'remember_token')
                        ->orderBy($orderField, $orderType)
                        ->orderBy('user_last_online', $orderType)
                        ->skip($start_from)
                        ->take(10)
                        ->get();
                } else {
                    $users = DB::table('users')
                        ->select('id', 'name', 'email', 'is_admin', 'created_at', 'updated_at', 'avatar', 'banner', 'cookie_choice', 'account_activated', 'activation_token', 'user_online', 'is_admin', 'user_last_online', 'user_last_ip', 'remember_token')
                        ->orderBy($orderField, $orderType)
                        ->skip($start_from)
                        ->take(10)
                        ->get();
                }


                foreach ($users as $user) {
                    $user_information = DB::table('user_information')
                        ->where('user_id', $user->id)
                        ->get();

                    $user->user_information = array();

                    if (count($user_information) != 0 && $user_information[0]) {
                        $user_information[0]->time_diff = time() - strtotime($user->user_last_online);
                        if ($user_information[0]->time_diff > 300) {
                            // Inactive for 5 minutes
                            $user->user_online = 0;
                        }

                        $user->user_information = $user_information[0];
                    }

                }

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

    // Update User Settings
    public function updateUser(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $user = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $user->is_admin;

            if ($userAdmin == 1) {

                $response = $request;

                // User
                $id = $request->only('id')['id'];
                $email = $request->only('email')['email'];
                $name = $request->only('name')['name'];
                $reset_avatar = $request->only('reset_avatar')['reset_avatar'];
                $reset_banner = $request->only('reset_banner')['reset_banner'];

                // User Information
                $birthday = $request->only('birthday')['birthday'];
                $city = $request->only('city')['city'];
                $country = $request->only('country')['country'];
                $department = $request->only('department')['department'];
                $phone = $request->only('phone')['phone'];
                $slogan = $request->only('slogan')['slogan'];
                $street = $request->only('street')['street'];

                // Administration
                $account_activated = $request->only('account_activated')['account_activated'];
                $is_admin = $request->only('is_admin')['is_admin'];
                $reset_password = $request->only('reset_password')['reset_password'];
                $new_password = $request->only('new_password')['new_password'];

                // Sanitize Input
                $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                $name = filter_var($name, FILTER_SANITIZE_STRING);
                $birthday = filter_var($birthday, FILTER_SANITIZE_STRING);
                $city = filter_var($city, FILTER_SANITIZE_STRING);
                $country = filter_var($country, FILTER_SANITIZE_STRING);
                $department = filter_var($department, FILTER_SANITIZE_STRING);
                $phone = filter_var($phone, FILTER_SANITIZE_STRING);
                $slogan = filter_var($slogan, FILTER_SANITIZE_STRING);
                $street = filter_var($street, FILTER_SANITIZE_STRING);
                $account_activated = filter_var($account_activated, FILTER_SANITIZE_NUMBER_INT);

                // Validate Input
                if (strlen($name) == 0) {
                    $response = array(
                        "status" => "field_name_required",
                        "error" => true,
                        "message" => "The field name is required."
                    );
                    return new JsonResponse($response);
                }

                if (strlen($email) == 0) {
                    $response = array(
                        "status" => "field_email_required",
                        "error" => true,
                        "message" => "The field email is required."
                    );
                    return new JsonResponse($response);
                }

                if ($reset_password) {
                    if (strlen($new_password) == 0) {
                        $response = array(
                            "status" => "field_new_password_required",
                            "error" => true,
                            "message" => "The field new password is required."
                        );
                        return new JsonResponse($response);
                    }

                    // Update User
                    DB::table('users')
                        ->where('id', $id)
                        ->update([
                            'password' => app('hash')->make($new_password),
                        ]);

                }

                // Update User
                DB::table('users')
                    ->where('id', $id)
                    ->update([
                        'name' => $name,
                        'email' => $email,
                        'account_activated' => $account_activated,
                        'is_admin' => $is_admin,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                // Reset Avatar
                if ($reset_avatar) {
                    DB::table('users')
                        ->where('id', $id)
                        ->update([
                            'avatar' => "",
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                }

                // Reset Banner
                if ($reset_banner) {
                    DB::table('users')
                        ->where('id', $id)
                        ->update([
                            'banner' => "",
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                }

                // Update User Information
                DB::table('user_information')
                    ->where('user_id', $id)
                    ->update([
                        'user_birthday' => $birthday,
                        'user_city' => $city,
                        'user_country' => $country,
                        'user_department' => $department,
                        'user_phone' => $phone,
                        'user_slogan' => $slogan,
                        'user_street' => $street,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                $response = array(
                    "status" => "ok",
                    "error" => false,
                    "message" => "The User has been updated successfully."
                );

                return new JsonResponse($response);

            } else {

                $response = array(
                    "status" => "0",
                    "error" => true,
                    "message" => "You are not authorized to perform this action."
                );

                return new JsonResponse($response);

            }
        }
    }
}
