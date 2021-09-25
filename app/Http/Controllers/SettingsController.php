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
    // Returns the value of a key
    public function getSettingValue($key = null) {

        if (isset($_GET["key"])) {
            $key = $_GET["key"];
        }

        if ($key == null) {
            return response()->json(array('status' => 0));
        }
        
        $return = DB::table('app_settings')
            ->select('setting_value')
            ->where('setting_key', $key)
            ->first();

        if (isset($_GET["key"])) {
            return response()->json(array('status' => 1, 'setting_value' => $return->setting_value));
        } else {
            return $return->setting_value;
        }

    }
}
