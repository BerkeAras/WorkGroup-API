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
use App\Http\Controllers\NotificationController;
use Intervention\Image\Facades\Image as Image;

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

            $id = "%";
            if (isset($request->only('id')["id"])) {
                $id = $request->only('id')["id"];
            }

            $hashtag = "%";
            if (isset($request->only('hashtag')["hashtag"])) {
                $hashtag = $request->only('hashtag')["hashtag"];
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
                ->join('users', 'users.id', '=', 'posts.user_id');

            if ($hashtag == "%") {
                $posts = $posts->select('posts.*', 'users.name', 'users.avatar', 'users.email');
                $posts = $posts->where('users.email', 'LIKE', $user);
                $posts = $posts->where('posts.group_id', 'LIKE', $group);
                $posts = $posts->where('posts.id', 'LIKE', $id);
            } else {
                $posts = $posts->leftJoin('post_topics', 'posts.id', '=', 'post_topics.post_id');
                $posts = $posts->select('posts.*', 'users.name', 'users.avatar', 'users.email', 'post_topics.post_id', 'post_topics.topic');
                $posts = $posts->where('post_topics.topic', $hashtag);
                $posts = $posts->groupBy('posts.id');
            }

            $currentUser = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $currentUser->is_admin;

            if ($userAdmin == 0) {
                $posts = $posts->where('posts.status', true);
            }

            $posts = $posts
                ->orderByRaw('posts.is_pinned DESC, posts.created_at DESC')
                ->skip($start_from)
                ->take($maxPosts)
                ->get()
                ->toArray();

            $postsCount = DB::table('posts')
                ->join('users', 'users.id', '=', 'posts.user_id');

                if ($hashtag == "%") {
                    $postsCount = $postsCount->select('posts.*', 'users.name', 'users.avatar', 'users.email');
                    $postsCount = $postsCount->where('users.email', 'LIKE', $user);
                    $postsCount = $postsCount->where('users.email', 'LIKE', $user);
                    $postsCount = $postsCount->where('posts.group_id', 'LIKE', $group);
                    $postsCount = $postsCount->where('posts.id', 'LIKE', $id);
                } else {
                    $postsCount = $postsCount->leftJoin('post_topics', 'posts.id', '=', 'post_topics.post_id');
                    $postsCount = $postsCount->select('posts.*', 'users.name', 'users.avatar', 'users.email', 'post_topics.post_id', 'post_topics.topic');
                    $postsCount = $postsCount->where('post_topics.topic', $hashtag);
                    $postsCount = $postsCount->groupBy('posts.id');
                }

            $postsCount = $postsCount->count();

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

    public function getCommentLikes(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (!isset($request->only('id')["id"])) {
                return 0;
            }

            $likes = DB::table('post_comment_likes')
                ->where("comment_id", $request->only('id')["id"])
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
    public function likeComment(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (!isset($request->only('id')["id"])) {
                return "unliked";
            }

            $likes = DB::table('post_comment_likes')
                ->where("comment_id", $request->only('id')["id"])
                ->where("user_id", json_decode(JWTAuth::parseToken()->authenticate(), true)["id"])
                ->get()
                ->count();

            if ($likes == 0) {
                DB::table('post_comment_likes')
                    ->insert(
                        [
                            'comment_id' => $request->only('id')["id"],
                            'user_id' => json_decode(JWTAuth::parseToken()->authenticate(), true)["id"],
                            "created_at" =>  date('Y-m-d H:i:s'),
                            "updated_at" => date('Y-m-d H:i:s'),
                        ]
                    );

                return "liked";
            } elseif ($likes == 1) {
                DB::table('post_comment_likes')
                    ->where("comment_id", $request->only('id')["id"])
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

                $comment->likes = DB::table('post_comment_likes')
                    ->where("comment_id", $comment->id)
                    ->get()
                    ->count();

                $has_liked = DB::table('post_comment_likes')
                    ->where("comment_id", $comment->id)
                    ->where("user_id", json_decode(JWTAuth::parseToken()->authenticate(), true)["id"])
                    ->count();
    
                if ($has_liked == 0) {
                    $comment->hasLiked = "";
                } else {
                    $comment->hasLiked = "liked";
                }

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

            $pattern = "/@\[(.*?)\]\((\d+)\)/";
            preg_match_all($pattern, $content, $matches, PREG_PATTERN_ORDER);

            // Sender
            $user = DB::table('users')
                ->where("id", $user_id)
                ->first();        
            
            for ($i = 0; $i < count($matches[1]); $i++) {

                $match_user_id = $matches[2][$i];

                $match_user = DB::table('users')
                    ->where("id", $match_user_id)
                    ->first();

                // Replace Comment with link
                $content = str_replace($matches[0][$i], "<a href='" . env("APP_URL") . "/app/user/" . $match_user->email . "'>@" . $match_user->name . "</a>", $content);

                // Send Notification
                $notification = new NotificationController();
                $notification->sendNotification(
                    $match_user_id,
                    $user_id,
                    "$user->name mentioned you in his comment.",
                    $user->name . " mentioned you in his comment: " . $content,
                    "/app/post/$post_id",
                    "comment_mention"
                );

                unset($notification);
                unset($match_user_id);
                unset($match_user);
            }

            if (DB::insert('insert into post_comments (user_id, post_id, comment_content, created_at, updated_at) values (?, ?, ?, ?, ?)', [$user_id, $post_id, $content, $created_at, $updated_at])) {

                // Get Post Owner
                $post = DB::table('posts')
                    ->where("id", $post_id)
                    ->first();

                // Send notification to user
                if ($post->user_id !== $user_id) {
                    $notification = new NotificationController();
                    $notification->sendNotification(
                        $post->user_id,
                        $user_id,
                        "A new comment has been added to your post",
                        $user->name . " commented on your post: " . $content,
                        "/app/post/$post_id",
                        "comment"
                    );
                }

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

                                // Get Compression Setting
                                $postImageQuality = DB::table('app_settings')
                                    ->where('config_key','other.post_image_quality')
                                    ->first();

                                if ($postImageQuality->config_value == "min") {
                                    $img = Image::make($upload_name);
                                    $img->resize(null, 512, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
                                    $img->save($upload_name, 40);
                                } elseif ($postImageQuality->config_value == "medium") {
                                    $img = Image::make($upload_name);
                                    $img->resize(null, 720, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
                                    $img->save($upload_name, 60);
                                }

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

                // Get Current User
                $user = DB::table('users')
                    ->where('id', $user_id)
                    ->first();

                // Send Report to Administrator
                $administrator = DB::table('users')
                    ->where('is_admin', 1)
                    ->where('account_activated', 1)
                    ->orderBy('user_online', 'desc')
                    ->first();

                $administrator_id = $administrator->id;

                $notification = new NotificationController();
                $notification->sendNotification(
                    $administrator_id,
                    $user_id,
                    "$user->name reported a post.",
                    $user->name . " just reported a post. Reason: $reportTypeValue. Details: $reportTextValue. Please check the post and take appropriate action.",
                    "/app/post/$postId",
                    "report"
                );

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

    public function pinPost(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $postId =  $request->only('postId')["postId"];

            $currentUser = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $currentUser->is_admin;

            if ($userAdmin == 0) {
                return new JsonResponse(["message" => "You are not authorized to perform this action."], 401);
            }

            $currentPinnedStatus = DB::table('posts')
                ->where('id', $postId)
                ->first();
            $currentPinnedStatus = $currentPinnedStatus->is_pinned;

            $pinnedStatus = 0;

            if ($currentPinnedStatus == 0) {
                $pinnedStatus = 1;
            } else {
                $pinnedStatus = 0;
            }

            // Update
            $updatedPost = DB::table('posts')
                ->where('id', $postId)
                ->update([
                    'is_pinned' => $pinnedStatus,
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);

            if ($updatedPost) {
                return response([
                    'error' => false,
                    'message' => 'Post (un-)pinned',
                    'is_pinned' => $pinnedStatus
                ]);
            } else {
                return response([
                    'error' => true,
                    'message' => 'Post could not be (un-)pinned',
                    'is_pinned' => $pinnedStatus
                ]);
            }

        }

    }

    public function togglePostStatus(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $postId =  $request->only('postId')["postId"];

            $currentUser = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $currentUser->is_admin;

            if ($userAdmin == 0) {
                return new JsonResponse(["message" => "You are not authorized to perform this action."], 401);
            }

            $currentSatus = DB::table('posts')
                ->where('id', $postId)
                ->first();
            $currentSatus = $currentSatus->status;

            $newStatus = 0;

            if ($currentSatus == 0) {
                $newStatus = 1;
            } else {
                $newStatus = 0;
            }

            // Update
            $updatedPost = DB::table('posts')
                ->where('id', $postId)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);

            if ($updatedPost) {
                return response([
                    'error' => false,
                    'message' => 'Post enabled/disabled',
                    'status' => $newStatus
                ]);
            } else {
                return response([
                    'error' => true,
                    'message' => 'Post could not be enabled/disabled',
                    'status' => $newStatus
                ]);
            }

        }

    }

    public function clearComments(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $postId =  $request->only('postId')["postId"];

            $currentUser = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $currentUser->is_admin;

            if ($userAdmin == 0) {
                return new JsonResponse(["message" => "You are not authorized to perform this action."], 401);
            }

            $comments = DB::table('post_comments')
                ->where('post_id', $postId)
                ->get();

            foreach ($comments as $comment) {
                $commentId = $comment->id;
                DB::table('post_comment_likes')
                    ->where('comment_id', $commentId)
                    ->delete();
                unset($commentId);
            }

            $deleteComments = DB::table('post_comments')
                ->where('post_id', $postId)
                ->delete();   

            if ($deleteComments) {
                return response([
                    'error' => false,
                    'message' => 'Comments cleared',
                ]);
            } else {
                return response([
                    'error' => true,
                    'message' => 'Comments could not be cleared',
                ]);
            }

        }

    }
    public function clearLikes(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $postId =  $request->only('postId')["postId"];

            $currentUser = DB::table('users')->where('id', $user_id)->first();
            $userAdmin = $currentUser->is_admin;

            if ($userAdmin == 0) {
                return new JsonResponse(["message" => "You are not authorized to perform this action."], 401);
            }

            $deleteComments = DB::table('post_likes')
                ->where('post_id', $postId)
                ->delete();

            // Get Comment Likes
            $comments = DB::table('post_comments')
                ->where('post_id', $postId)
                ->get();

            foreach ($comments as $comment) {
                $commentId = $comment->id;
                DB::table('post_comment_likes')
                    ->where('comment_id', $commentId)
                    ->delete();
                unset($commentId);
            }

            if ($deleteComments) {
                return response([
                    'error' => false,
                    'message' => 'Likes cleared',
                ]);
            } else {
                return response([
                    'error' => true,
                    'message' => 'Likes could not be cleared',
                ]);
            }

        }

    }

}
