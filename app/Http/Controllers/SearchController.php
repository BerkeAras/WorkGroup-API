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

                // Users
                $searchUsers = DB::table('users')
                    ->leftJoin('user_information', 'users.id', '=', 'user_information.user_id')
                    ->where('users.email', 'LIKE', "%$searchQuery%")
                    ->orWhere('users.name', 'LIKE', "%$searchQuery%")
                    ->orWhere('user_information.user_department', 'LIKE', "%$searchQuery%")
                    ->limit(4)
                    ->get();

                array_push($resultsArray, $searchUsers);

                // Groups
                $searchGroups = DB::table('groups')
                    ->select('groups.*', 'group_tags.group_id', 'group_tags.tag')
                    ->leftJoin('group_tags', 'groups.id', '=', 'group_tags.group_id')
                    ->where('groups.group_title', 'LIKE', "%$searchQuery%")
                    ->orWhere('groups.group_description', 'LIKE', "%$searchQuery%")
                    ->orWhere('group_tags.tag', 'LIKE', "%$searchQuery%")
                    ->limit(4)
                    ->get();

                // Remove duplicates from groups
                $searchGroupsUnique = array();
                foreach ($searchGroups as $v) {
                    if (isset($searchGroupsUnique[$v->id])) {
                        // found duplicate
                        continue;
                    }
                    // remember unique item
                    $searchGroupsUnique[$v->id] = $v;
                }

                array_push($resultsArray, $searchGroupsUnique);

                // Hashtags
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

                // KnowledgeBase Folders
                $knowledgeBaseFolders = DB::table('knowledge_base_folders')
                    ->where('knowledge_base_folder_name', 'LIKE', "%$searchQuery%")
                    ->orWhere('knowledge_base_folder_description', 'LIKE', "%$searchQuery%")
                    ->orWhere('id', $searchQuery)
                    ->limit(4)
                    ->get();

                array_push($resultsArray, $knowledgeBaseFolders);

                // KnowledgeBase Files
                $knowledgeBaseFiles = DB::table('knowledge_base_files')
                    ->where('knowledge_base_file_name', 'LIKE', "%$searchQuery%")
                    ->orWhere('knowledge_base_file_slug', 'LIKE', "%$searchQuery%")
                    ->orWhere('knowledge_base_file_path', 'LIKE', "%$searchQuery%")
                    ->orWhere('knowledge_base_file_description', 'LIKE', "%$searchQuery%")
                    ->orWhere('id', $searchQuery)
                    ->limit(4)
                    ->get();

                array_push($resultsArray, $knowledgeBaseFiles);

                return response()->json($resultsArray);
            } else {
                return response([
                    'message' => 'No search query given'
                ]);
            }

        }

    }
}
