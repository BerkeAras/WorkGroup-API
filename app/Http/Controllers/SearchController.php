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

class SearchController extends Controller
{
    public function searchQuery(Request $request) {
        
        if (JWTAuth::parseToken()->authenticate()) {

            if (isset($request->only('query')["query"])) {

                $searchQuery = $request->only('query')["query"];
                $resultsArray = array();

                $searchUsers = DB::table('users')
                    ->select('id', 'email', 'name')
                    ->where('email', 'like', "%$searchQuery%")
                    ->orWhere('name', 'like', "%$searchQuery%")
                    ->orWhere('id', $searchQuery)
                    ->limit(4)
                    ->get();
                array_push($resultsArray, $searchUsers);
                
                $searchTopics = DB::table('post_topics')
                    ->select('id', 'post_id', 'topic')
                    ->where('topic', 'like', "%$searchQuery%")
                    ->groupBy('topic')
                    ->limit(4)
                    ->get();
                array_push($resultsArray, $searchTopics);
                
        
                return response()->json($resultsArray);
            } else {
                return response([
                    'message' => 'No search query given'
                ]);
            }

        }

    }
}
