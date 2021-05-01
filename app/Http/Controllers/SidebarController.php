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

class SidebarController extends Controller
{
    // Returns the most popular topics
    public function popular(Request $request) {
        
        if (JWTAuth::parseToken()->authenticate()) {
            $topics = DB::table('post_topics')
                ->select('id', 'topic', DB::raw('count(*) as total'))
                ->groupBy('topic')
                ->orderByRaw('total DESC')
                ->get();
    
            return response()->json($topics);
        }

    }
}
