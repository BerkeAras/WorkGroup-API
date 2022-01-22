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
            $content = trim($content);
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $created_at = date('Y-m-d H:i:s', time());
            $updated_at = date('Y-m-d H:i:s', time());
            $group_id = 0;
            $status = 1;

            if (isset($request->only('groupId')["groupId"])) {
                $isGroupMember = DB::table('group_members')
                    ->where('user_id', $user_id)
                    ->count();

                if ($isGroupMember > 0) {
                    $group_id = $request->only('groupId')["groupId"];
                }
            }

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

            $insertedPost = DB::table('posts')->insertGetId(
                [
                    'user_id' => $user_id,
                    'group_id' => $group_id,
                    'post_content' => $content,
                    'status' => $status,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                ]
            );

            if ($insertedPost) {
                
                $id = $insertedPost;

                if ($request->only('images')["images"]) {

                    $images = $request->only('images')["images"];
                    $images = json_decode($images);

                    foreach ($images as $image) {
                        DB::insert('insert into post_images (post_id, post_image_url, created_at, updated_at) values (?, ?, ?, ?)', [$id, $image, $created_at, $updated_at]);
                    }

                }
                
                if ($request->only('files')["files"]) {

                    $files = $request->only('files')["files"];
                    $files = json_decode($files);

                    foreach ($files as $file) {
                        DB::insert('insert into post_files (post_id, post_file_original, post_file_url, created_at, updated_at) values (?, ?, ?, ?, ?)', [$id, $file[0], $file[1], $created_at, $updated_at]);
                    }

                }

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

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            DB::enableQueryLog();

            if (isset($request->only('page')["page"])) {
                $page = $request->only('page')["page"];
            } else {
                $page = 1;
            }

            $user = "%";
            if (isset($request->only('user')["user"])) {
                $user = $request->only('user')["user"];
            }

            $group = "%";
            $isGroupMember = false;
            if (isset($request->only('group')["group"])) {
                $isGroupPrivate = DB::table('groups')
                    ->where('id', $request->only('group')["group"])
                    ->select('group_private')
                    ->get()
                    ->toArray();

                if ($isGroupPrivate[0]->group_private == 1) {
                    $isGroupMember = DB::table('group_members')
                        ->where('group_id', $request->only('group')["group"])
                        ->where('user_id', $user_id)
                        ->count();

                    if ($isGroupMember == 0) {
                        return response()->json(array('status' => 'not_member'));
                    } else {
                        $group = $request->only('group')["group"];
                    }
                } else {
                    $group = $request->only('group')["group"];
                }

            }

            // Check maximum posts per page
            $maxPosts = DB::table('app_settings')
                ->where('config_key','app.maximum_posts_per_page')
                ->get()
                ->toArray();

            $maxPosts = $maxPosts[0]->config_value;

	        $start_from = ($page-1) * $maxPosts; // 20 Items per page

            $posts = DB::table('posts')
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('posts.*', 'users.name', 'users.avatar', 'users.email')
                ->where('users.email', 'LIKE', $user)
                ->where('posts.group_id', 'LIKE', $group)
                ->orderByRaw('posts.created_at DESC')
                ->skip($start_from)
                ->take($maxPosts)
                ->get()
                ->toArray();

            $postsCount = DB::table('posts')
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->select('posts.*', 'users.name', 'users.avatar', 'users.email')
                ->where('users.email', 'LIKE', $user)
                ->where('posts.group_id', 'LIKE', $group)
                ->count();

            $total_records = $postsCount;
            $total_pages = ceil($total_records / $maxPosts);

            foreach ($posts as $post_key => $post) {

                // Post Likes
                $likes = DB::table('post_likes')
                ->where("post_id", $post->id)
                ->count();

                $has_liked = DB::table('post_likes')
                ->where("post_id", $post->id)
                ->where("user_id", json_decode(JWTAuth::parseToken()->authenticate(), true)["id"])
                ->count();

                if ($has_liked == 0) {
                    $post->hasLiked = "";
                } else {
                    $post->hasLiked = "liked";
                }

                // Post Comments
                $comments = DB::select("SELECT * FROM post_comments WHERE post_id = '$post->id'");
                $comments = count($comments);

                // Post Images
                $images = DB::table('post_images')
                ->where("post_id", $post->id)
                ->get();

                // Post Files
                $files = DB::table('post_files')
                ->where("post_id", $post->id)
                ->get();

                // Post Group
                $group = [];
                $canReadPost = true;
                if ($post->group_id !== 0) {

                    $canReadPost = false;

                    $group = DB::table('groups')
                        ->where("id", $post->group_id)
                        ->get();
                    $group = $group[0];

                    // Check if user can read group-post
                        // Check if group is private
                        $isGroupPrivate = DB::table('groups')
                            ->where('id', $post->group_id)
                            ->select('group_private')
                            ->get()
                            ->toArray();
                        if ($isGroupPrivate[0]->group_private == 1) {

                            // Check if user is group member
                            $isGroupMember = DB::table('group_members')
                                ->where('group_id', $post->group_id)
                                ->where('user_id', $user_id)
                                ->count();
                            if ($isGroupMember == 0) {
                                $canReadPost = false;
                            } else {
                                $canReadPost = true;
                            }

                        } else {
                            $canReadPost = true;
                        }
                }

                if (!$canReadPost) {
                    unset($posts[$post_key]);
                    continue;
                }

                $post->likes = $likes;
                $post->comments = $comments;
                $post->created_at = $post->created_at;
                $post->updated_at = $post->updated_at;
                $post->images = $images;
                $post->files = $files;
                $post->group = $group;
            }

            $returnArray = array(
                'posts' => $posts,
                'total_pages' => (int)$total_pages,
                'current_page' => (int)$page,
                'total_records' => $total_records
            );

            return response()->json($returnArray);

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

            foreach ($comments as $comment) {
                $comment->created_at = date("m/d/Y H:i:s", strtotime($comment->created_at));
                $comment->updated_at = date("m/d/Y H:i:s", strtotime($comment->updated_at));
            }

            return response()->json($comments);

        }

    }

    public function createComment(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {
            $content =  $request->only('content')["content"];
            $content = trim($content);
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

    public function uploadImage(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $response = array();
            $upload_dir = './static/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if ($_FILES["image"]) {

                $image_name = $_FILES["image"]["name"];
                $image_tmp_name = $_FILES["image"]["tmp_name"];
                $error = $_FILES["image"]["error"];
                $size = $_FILES["image"]["size"];
                $file_type = $_FILES['image']['type']; //returns the mimetype

                // Check file size
                if ($size > (env("MAX_UPLOAD_SIZE") * 1000000) || $size == 0) {
                    $response = array(
                        "status" => "error",
                        "error" => true,
                        "message" => "File too big!"
                    );
                } else {

                    $allowed = array("image/jpeg", "image/jpg", "image/tiff", "image/gif", "image/png", "image/svg");
                    if(!in_array($file_type, $allowed)) {
                        $response = array(
                            "status" => "error",
                            "error" => true,
                            "message" => "Error uploading the file!"
                        );
                    } else {
                        if($error > 0){
                            $response = array(
                                "status" => "error",
                                "error" => true,
                                "message" => "Error uploading the file!"
                            );
                        } else {
                            $random_name = "pi-" . rand(1000,1000000) . time() . "-" . $image_name;
                            $upload_name = $upload_dir . strtolower($random_name);
                            $upload_name = preg_replace('/\s+/', '-', $upload_name);
        
                            if (move_uploaded_file($image_tmp_name , $upload_name)) {
                                $response = array(
                                    "status" => "success",
                                    "error" => false,
                                    "message" => "File uploaded successfully",
                                    "url" => $upload_name,
                                    "original_url" => $image_name
                                );
                            } else {
                                $response = array(
                                    "status" => "error",
                                    "error" => true,
                                    "message" => "Error uploading the file!"
                                );
                            }
                        }
                    }

                }


            } else {
                $response = array(
                    "status" => "error",
                    "error" => true,
                    "message" => "No file was sent!"
                );
            }

            return $response;

        }

    }

    public function uploadFile(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $response = array();
            $upload_dir = './static/files/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if ($_FILES["file"]) {

                $file_name = $_FILES["file"]["name"];
                $file_tmp_name = $_FILES["file"]["tmp_name"];
                $error = $_FILES["file"]["error"];
                $size = $_FILES["file"]["size"];

                // Check file size
                if ($size > (env("MAX_UPLOAD_SIZE") * 1000000) || $size == 0) {
                    $response = array(
                        "status" => "error",
                        "error" => true,
                        "message" => "File too big!"
                    );
                } else {
                    if($error > 0){
                        $response = array(
                            "status" => "error",
                            "error" => true,
                            "message" => "Error uploading the file!"
                        );
                    } else {
                        $random_name = "file-" . rand(1000,1000000) . time() . "-" . $file_name;
                        $upload_name = $upload_dir . strtolower($random_name);
                        $upload_name = preg_replace('/\s+/', '-', $upload_name);
    
                        if (move_uploaded_file($file_tmp_name , $upload_name)) {
                            $response = array(
                                "status" => "success",
                                "error" => false,
                                "message" => "File uploaded successfully",
                                "url" => $upload_name,
                                "original_url" => $file_name
                            );
                        } else {
                            $response = array(
                                "status" => "error",
                                "error" => true,
                                "message" => "Error uploading the file!"
                            );
                        }
                    }
                }


            } else {
                $response = array(
                    "status" => "error",
                    "error" => true,
                    "message" => "No file was sent!"
                );
            }

            return $response;

        }

    }

    public function reportPost(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $postId =  $request->only('postId')["postId"];
            $reportTypeValue =  $request->only('reportTypeValue')["reportTypeValue"];
            $reportTextValue =  $request->only('reportTextValue')["reportTextValue"];
            $created_at = date('Y-m-d H:i:s', time());
            $updated_at = date('Y-m-d H:i:s', time());

            $insertedPostReport = DB::table('post_reports')->insertGetId(
                [
                    'user_id' => $user_id,
                    'post_id' => $postId,
                    'report_reason' => $reportTypeValue,
                    'report_text' => $reportTextValue,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                ]
            );

            if ($insertedPostReport) {
                return response([
                    'error' => false,
                    'message' => 'Post reported'
                ]);
            } else {
                return response([
                    'error' => true,
                    'message' => 'Post could not be reported'
                ]);
            }

        }

    }

}
