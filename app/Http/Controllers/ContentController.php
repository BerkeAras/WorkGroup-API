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

            DB::enableQueryLog(); 

            if (isset($request->only('from')["from"])) {
                $page = $request->only('from')["from"];
            } else {
                $page = 1;
            }
            $results_per_page = 30;
            $start_from = ($page-1) * $results_per_page;

            $posts = DB::table('posts')
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('posts.*', 'users.name', 'users.avatar', 'users.email')
                ->orderByRaw('posts.id DESC')
                ->limit($results_per_page)
                ->offset($start_from)
                ->get()
                ->toArray();
    
            foreach ($posts as $post) {

                $likes = DB::table('post_likes')
                ->where("post_id", $post->id)
                ->count();

                $comments = DB::select("SELECT * FROM post_comments WHERE post_id = '$post->id'");
                $comments = count($comments);
                
                $has_liked = DB::table('post_likes')
                ->where("post_id", $post->id)
                ->where("user_id", json_decode(JWTAuth::parseToken()->authenticate(), true)["id"])
                ->count();

                if ($has_liked == 0) {
                    $post->hasLiked = "";
                } else {
                    $post->hasLiked = "liked";
                }

                $post->likes = $likes;
                $post->comments = $comments;
            }

            return response()->json($posts);

        }

    }

    public function getLikes(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (!isset($request->only('id')["id"])) {
                return 0;
            }

            $likes = DB::table('post_likes')
                ->where("post_id", $request->only('id')["id"])
                ->get()
                ->count();
    
            return $likes;

        }

    }
    
    public function likePost(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (!isset($request->only('id')["id"])) {
                return "unliked";
            }

            $likes = DB::table('post_likes')
                ->where("post_id", $request->only('id')["id"])
                ->where("user_id", json_decode(JWTAuth::parseToken()->authenticate(), true)["id"])
                ->get()
                ->count();

            if ($likes == 0) {
                DB::table('post_likes')
                    ->insert(
                        [
                            'post_id' => $request->only('id')["id"],
                            'user_id' => json_decode(JWTAuth::parseToken()->authenticate(), true)["id"]
                        ]
                    );

                return "liked";
            } elseif ($likes == 1) {
                DB::table('post_likes')
                    ->where("post_id", $request->only('id')["id"])
                    ->where("user_id", json_decode(JWTAuth::parseToken()->authenticate(), true)["id"])
                    ->delete();

                return "unliked";
            }

        }

    }

    public function getComments(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (!isset($request->only('id')["id"])) {
                return 0;
            }

            $comments = DB::table('post_comments')
                ->join('users', 'users.id', '=', 'post_comments.user_id')
                ->select('post_comments.*', 'users.name', 'users.avatar', 'users.email')
                ->orderByRaw('post_comments.id DESC')
                ->where("post_comments.post_id", $request->only('id'))
                ->get()
                ->toArray();

            return response()->json($comments);

        }

    }

    public function createComment(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {
            $content =  $request->only('content')["content"];
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $created_at = date('Y-m-d H:i:s', time());
            $updated_at = date('Y-m-d H:i:s', time());
            $post_id = $request->only('post_id')["post_id"];

            $content = str_replace('<br>', '{{BR}}', $content);
            $content = preg_replace('/<[^>]*>/', '', $content);
            $content = str_replace('{{BR}}', '<br>', $content);

            if (DB::insert('insert into post_comments (user_id, post_id, comment_content, created_at, updated_at) values (?, ?, ?, ?, ?)', [$user_id, $post_id, $content, $created_at, $updated_at])) {

                return 1;
            } else {
                return 0;
            }

        }

    }
}
