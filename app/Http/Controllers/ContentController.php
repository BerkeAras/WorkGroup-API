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

class ContentController extends Controller
{
    // Creates new content
    public function createPost(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {
            $content =  $request->only('content')["content"];
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $created_at = date('Y-m-d H:i:s', time());
            $updated_at = date('Y-m-d H:i:s', time());
            $group_id = 0;
            $status = 1;

            if (DB::insert('insert into posts (user_id, group_id, post_content, status, created_at, updated_at) values (?, ?, ?, ?, ?, ?)', [$user_id, $group_id, $content, $status, $created_at, $updated_at])) {
                return 1;
            } else {
                return 0;
            }

        }

    }
}
