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

                // Posts
                $searchPosts = DB::table('posts')
                    ->select('posts.*', 'post_comments.post_id', 'post_comments.comment_content')
                    ->leftJoin('post_comments', 'posts.id', '=', 'post_comments.post_id')
                    ->where('posts.post_content', 'LIKE', "%$searchQuery%")
                    ->orWhere('post_comments.comment_content', 'LIKE', "%$searchQuery%")
                    ->orWhere('posts.id', 'LIKE', "$searchQuery")
                    ->limit(4)
                    ->get();

                // Remove duplicates from groups
                $searchPostsUnique = array();
                foreach ($searchPosts as $v) {
                    if (isset($searchPostsUnique[$v->id])) {
                        // found duplicate
                        continue;
                    }
                    // remember unique item
                    $searchPostsUnique[$v->id] = $v;
                }

                // Remove if user should not see this post
                foreach ($searchPostsUnique as $searchPostsUniqueItem) {

                    $userId = JWTAuth::parseToken()->authenticate()->id;
                    $userIsAdmin = DB::table('users')
                        ->where('id', $userId)
                        ->where('is_admin', 1)
                        ->count();

                    $newContent = strlen($searchPostsUniqueItem->post_content) > 30 ? substr($searchPostsUniqueItem->post_content,0,30)."..." : $searchPostsUniqueItem->post_content;

                    $searchPostsUnique[$searchPostsUniqueItem->id]->post_content = strip_tags($newContent);

                    if ($searchPostsUniqueItem->status == 0) {
                        if ($userIsAdmin == 0) {
                            unset($searchPostsUnique[$searchPostsUniqueItem->id]);
                        }
                    } else {
                        if ($searchPostsUniqueItem->group_id != 0) {
                            // Check if user is in the group
                            $groupId = $searchPostsUniqueItem->group_id;
    
                            // Check if group is private
                            $groupIsPrivate = DB::table('groups')
                                ->where('id', $groupId)
                                ->where('group_private', 1)
                                ->count();
    
                            if ($groupIsPrivate == 1) {
                                $userInGroup = DB::table('group_members')
                                    ->where('group_id', $groupId)
                                    ->where('user_id', $userId)
                                    ->count();
        
                                if ($userInGroup == 0) {
                                    // User is not in the group
                                    
                                    // Check if user is admin
                                    if ($userIsAdmin == 0) {
                                        unset($searchPostsUnique[$searchPostsUniqueItem->id]);
                                    }
                                }
                            }   
                        }
                    }

                }

                array_push($resultsArray, $searchPostsUnique);

                return response()->json($resultsArray);
            } else {
                return response([
                    'message' => 'No search query given'
                ]);
            }

        }

    }
}
