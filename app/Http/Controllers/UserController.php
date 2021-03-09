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

class UserController extends Controller
{

    public function getBanner(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (isset($request->only('email')["email"])) {
                $email = $request->only('email')["email"];

                $banner = DB::table('users')
                    ->select('banner', 'avatar')
                    ->where('email', $email)
                    ->get();
        
                return response()->json($banner);

            } else {
                return response([
                    'message' => 'No email given'
                ]);
            }

        }

    }
    
    public function uploadBanner(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            // File Upload
            $response = array();
            $upload_dir = './static/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if ($_FILES['banner']) {
                $banner_name = $_FILES["banner"]["name"];
                $banner_tmp_name = $_FILES["banner"]["tmp_name"];
                $error = $_FILES["banner"]["error"];

                if($error > 0){
                    $response = array(
                        "status" => "error",
                        "error" => true,
                        "message" => "Error uploading the file!"
                    );
                } else {
                    $random_name = rand(1000,1000000) . time() . "-" . $banner_name;
                    $upload_name = $upload_dir . strtolower($random_name);
                    $upload_name = preg_replace('/\s+/', '-', $upload_name);

                    if (move_uploaded_file($banner_tmp_name , $upload_name)) {

                        DB::table('users')
                            ->where('id', $user_id)
                            ->update(['banner' => $upload_name]);

                        $response = array(
                            "status" => "success",
                            "error" => false,
                            "message" => "File uploaded successfully",
                            "url" => $upload_name
                        );
                    } else {
                        $response = array(
                            "status" => "error",
                            "error" => true,
                            "message" => "Error uploading the file!"
                        );
                    }
                }    

            } else {
                $response = array(
                    "status" => "error",
                    "error" => true,
                    "message" => "No file was sent!"
                );
            }

            echo json_encode($response);


        }
        /*
        
         

        
        */
    }
    
    public function uploadAvatar(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            // File Upload
            $response = array();
            $upload_dir = './static/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if ($_FILES['avatar']) {
                $avatar_name = $_FILES["avatar"]["name"];
                $avatar_tmp_name = $_FILES["avatar"]["tmp_name"];
                $error = $_FILES["avatar"]["error"];

                if($error > 0){
                    $response = array(
                        "status" => "error",
                        "error" => true,
                        "message" => "Error uploading the file!"
                    );
                } else {
                    $random_name = "a-" . rand(1000,1000000) . time() . "-" . $avatar_name;
                    $upload_name = $upload_dir . strtolower($random_name);
                    $upload_name = preg_replace('/\s+/', '-', $upload_name);

                    if (move_uploaded_file($avatar_tmp_name , $upload_name)) {

                        DB::table('users')
                            ->where('id', $user_id)
                            ->update(['avatar' => $upload_name]);

                        $response = array(
                            "status" => "success",
                            "error" => false,
                            "message" => "File uploaded successfully",
                            "url" => $upload_name
                        );
                    } else {
                        $response = array(
                            "status" => "error",
                            "error" => true,
                            "message" => "Error uploading the file!"
                        );
                    }
                }    

            } else {
                $response = array(
                    "status" => "error",
                    "error" => true,
                    "message" => "No file was sent!"
                );
            }

            echo json_encode($response);


        }
        /*
        
         

        
        */
    }

    public function setupUser(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $slogan = $request->only('slogan')["slogan"];
            $country = $request->only('country')["country"];
            $city = $request->only('city')["city"];
            $street = $request->only('street')["street"];
            $department = $request->only('department')["department"];
            $birthday = $request->only('birthday')["birthday"];
            $phone = $request->only('phone')["phone"];

            $created_at = date('Y-m-d H:i:s', time());
            $updated_at = date('Y-m-d H:i:s', time());

            DB::insert('insert into user_information (user_id, user_slogan, user_country, user_city, user_street, user_department, user_birthday, user_phone, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $user_id,
                $slogan,
                $country,
                $city,
                $street,
                $department,
                $birthday,
                $phone,
                $created_at,
                $updated_at
            ]);

            return response([
                'message' => 'Setup successfully created'
            ]);

        }

    }

}
