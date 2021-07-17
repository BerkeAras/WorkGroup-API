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
                    ->leftJoin('user_information', 'users.id', '=', 'user_information.user_id')
                    ->select('users.id', 'users.email', 'users.name', 'user_information.user_department')    
                    ->where('users.email', 'like', "%$searchQuery%")
                    ->orWhere('users.email', 'SOUNDS LIKE', $searchQuery)
                    ->orWhere('users.name', 'like', "%$searchQuery%")
                    ->orWhere('users.name', 'SOUNDS LIKE', $searchQuery)
                    ->orWhereRaw('MATCH (users.name) AGAINST ("' . preg_replace('/(\w+)/', '+$1', $searchQuery) . '" IN BOOLEAN MODE)')
                    ->orWhere('user_information.user_department', 'like', "%$searchQuery%")
                    ->orWhere('user_information.user_department', 'SOUNDS LIKE', $searchQuery)
                    ->orWhereRaw('MATCH (user_information.user_department) AGAINST ("' . preg_replace('/(\w+)/', '+$1', $searchQuery) . '" IN BOOLEAN MODE)')
                    ->orWhere('users.id', $searchQuery)
                    ->limit(4)
                    ->get();
                array_push($resultsArray, $searchUsers);
                
                if ($searchQuery[0] == "#" && !preg_match('/\s/',$searchQuery)) {
                    // Hashtag
                    $searchTopics = DB::table('post_topics')
                        ->select('id', 'post_id', 'topic')
                        ->where('topic', str_replace("#", "", $searchQuery))
                        ->groupBy('topic')
                        ->limit(4)
                        ->get();
                    array_push($resultsArray, $searchTopics);
                } else {
                    $searchTopics = DB::table('post_topics')
                        ->select('id', 'post_id', 'topic')
                        ->where('topic', 'LIKE', "%$searchQuery%")
                        ->groupBy('topic')
                        ->limit(4)
                        ->get();
                    array_push($resultsArray, $searchTopics);
                }

                
        
                return response()->json($resultsArray);
            } else {
                return response([
                    'message' => 'No search query given'
                ]);
            }

        }

    }
}
