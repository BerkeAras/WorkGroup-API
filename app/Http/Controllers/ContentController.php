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



            // Hashtags

            function get_hashtags($string, $str = 1) {
                preg_match_all('/#(\w+)/',$string,$matches);
                $i = 0;
                $keywords = "";
                if ($str) {
                    foreach ($matches[1] as $match) {
                        $count = count($matches[1]);
                        $keywords .= "$match";
                        $i++;
                        if ($count > $i) $keywords .= ", ";
                    }
                } else {
                    foreach ($matches[1] as $match) {
                        $keyword[] = $match;
                    }
                    $keywords = $keyword;
                }
                return $keywords;
            }

            if (DB::insert('insert into posts (user_id, group_id, post_content, status, created_at, updated_at) values (?, ?, ?, ?, ?, ?)', [$user_id, $group_id, $content, $status, $created_at, $updated_at])) {
                
                $column_id = DB::table('posts')->where([['user_id', '=', $user_id],['post_content', '=', $content]])->get();
                $id = $column_id[0]->id;

                $hashtagsArray = explode(',', get_hashtags($content));
                if (count($hashtagsArray) > 0) {
                    foreach ($hashtagsArray as $hashtag) {

                        $hashtag = trim($hashtag);

                        if ($hashtag !== "") {
                            DB::insert('insert into post_topics (user_id, post_id, topic, created_at, updated_at) values (?, ?, ?, ?, ?)', [$user_id, $id, $hashtag, $created_at, $updated_at]);
                        }

                    }
                }

                return 1;
            } else {
                return 0;
            }

        }

    }

    public function getPosts(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (isset($request->only('from')["from"])) {
                $page = $request->only('from')["from"];
            } else {
                $page = 1;
            }
            $results_per_page = 10;
            $start_from = ($page-1) * $results_per_page;

            $posts = DB::table('posts')
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('posts.*', 'users.name')
                ->orderByRaw('posts.id DESC')
                ->limit($results_per_page)
                ->offset($start_from)
                ->get();
    
            return response()->json($posts);

        }

    }
}
