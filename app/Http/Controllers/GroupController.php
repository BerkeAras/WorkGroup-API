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
use Illuminate\Support\Facades\Mail;
use App\Mail\GroupRequestMail;
use App\Mail\GroupRequestApprovedMail;
use App\Mail\GroupRequestRejectedMail;

class GroupController extends Controller
{
    
    // Returns group information
    public function getGroupInformation(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            if (isset($request->only('id')["id"])) {
                $id = $request->only('id')["id"];
                $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

                $group_information = DB::table('groups')
                    ->where([['id', $id],['status','1']])
                    ->get();
                    
                if (count($group_information) == 0) {
                    return [];
                }

                $group_members = DB::table('group_members')
                    ->join('users', 'users.id', '=', 'group_members.user_id')
                    ->select('users.name', 'users.avatar', 'users.banner', 'users.email', 'users.user_online', 'users.user_last_online', 'group_members.group_id', 'group_members.user_id', 'group_members.is_admin', 'group_members.created_at', 'group_members.updated_at')
                    ->where('group_members.group_id', $id)
                    ->get();

                $user = DB::table('users')
                    ->where('id', $user_id)
                    ->first();

                $isGroupMember = DB::table('group_members')
                    ->where('user_id', $user_id)
                    ->where('group_id', $id)
                    ->get()
                    ->toArray();

                $return = $group_information[0];
                $return->members = $group_members;
                $return->member_count = count($group_members);
                $return->is_admin = false;

                if (count($isGroupMember) > 0) {
                    $return->is_group_member = true;
                    if ($isGroupMember[0]->is_admin == true) {
                        $return->is_admin = true;
                    }
                } else {
                    $return->is_group_member = false;
                }

                if ($return->is_admin == false) {
                    $return->is_admin = $user->is_admin;
                }
                
                return response()->json($return);
            }
            

        }

    }

    // Return all groups
    public function getGroups(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $groups = DB::table('groups')
                ->where('status','1')
                ->get();

            foreach ($groups as $group) {
                $group_members = DB::table('group_members')
                    ->join('users', 'users.id', '=', 'group_members.user_id')
                    ->select('users.name', 'users.avatar', 'users.banner', 'users.email', 'users.user_online', 'users.user_last_online', 'group_members.group_id', 'group_members.user_id', 'group_members.is_admin', 'group_members.created_at', 'group_members.updated_at')
                    ->where('group_members.group_id', $group->id)
                    ->get();

                $group_requests = DB::table('group_requests')
                    ->where([['group_id', $group->id],['user_id', $user_id]])
                    ->get()
                    ->toArray();

                    $group->hasAlreadyRequested = false;
                    $group->requestId = 0;
                    foreach ($group_requests as $group_request) {
                        if ($group_request->status == "pending") {
                            $group->hasAlreadyRequested = true;
                            $group->requestId = $group_request->id;
                    }
                }

                $group->isInGroup = false;
                foreach ($group_members as $group_member) {
                    if ($group_member->user_id == $user_id) {
                        $group->isInGroup = true;
                    }
                }

                $group->member_count = count($group_members);
                $group->members = $group_members;
            }

            return response()->json(array('group_count' => count($groups), 'groups' => $groups));

        }
    }

    // Return all tags
    public function getTags(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {

            $group_tags = DB::table('group_tags')
                ->get()
                ->toArray();

            return response()->json($group_tags);
        }
    }

    // Join group
    public function joinGroup(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];
            $group_id = $request->only('id')["id"];
            
            $group = DB::table('groups')
                ->where('id', $group_id)
                ->get()
                ->toArray();

            if ($group[0]->group_private == "1") {
                $requestInsertId = DB::table('group_requests')->insertGetId([
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'status' => 'pending',
                    "created_at" =>  date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);

                // Send E-Mail to all administrators of the group.
                $admins = DB::table('group_members')
                    ->where([
                        ['group_id', $group_id],
                        ['is_admin', 1]
                    ])
                    ->get()
                    ->toArray();

                $request_user = DB::table('users')
                    ->where('id', $user_id)
                    ->get();
                $request_user = $request_user[0];

                forEach($admins as $admin) {

                    $admin_user_id = $admin->user_id;

                    $admin_user = DB::table('users')
                        ->where('id',$admin_user_id)
                        ->get();

                    Mail::send(new GroupRequestMail($admin_user[0]->email, env("APP_URL"), $request_user->name,$request_user->email,$group[0]->group_title,$group_id,$requestInsertId));

                }

                return response()->json(array('status' => 'pending'));
            } else {
                DB::table('group_requests')->insert([
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'status' => 'approved',
                    "created_at" =>  date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);
                DB::table('group_members')->insert([
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    "created_at" =>  date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);

                return response()->json(array('status' => 'approved'));
            }

        }
    }

    // Get user group memberships
    public function getGroupMemberships(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            $groupMemberships = DB::table('group_members')
                ->where('user_id', $user_id)
                ->get();

            if (count($groupMemberships) == 0) {
                $return = array('status' => 0);
            } else {
                
                foreach ($groupMemberships as $groupMembership) {
                    $group = DB::table('groups')
                        ->where('id', $groupMembership->group_id)
                        ->get();
                    $groupMembership->group_title = $group[0]->group_title;
                    $groupMembership->group_id = $group[0]->id;
                }
                
                $return = array('status' => 1, 'groups' => $groupMemberships);
            }
            
            return response()->json($return);
        }
    }

    // Create new group
    public function createGroup(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            if (
                isset($request->only('title')["title"]) &&
                isset($request->only('description')["description"]) &&
                isset($request->only('private')["private"])
            ) {
                $title = $request->only('title')["title"];
                $description = $request->only('description')["description"];
                $private = $request->only('private')["private"];

                $title = trim($title);
                $description = trim($description);

                $avatar = "";
                $banner = "";

                if ($private == "true") {$private = "1";}
                else {$private = "0";}

                if (isset($request->only('avatar')["avatar"])) {
                    
                    // Avatar Upload
                    $response = array();
                    $upload_dir = './static/';

                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $avatar_name = $_FILES["avatar"]["name"];
                    $avatar_tmp_name = $_FILES["avatar"]["tmp_name"];
                    $error = $_FILES["avatar"]["error"];

                    if($error > 0){
                        $response = array(
                            "status" => 0,
                            "error" => true,
                            "message" => "Error uploading the file!"
                        );
                    } else {
                        $random_name = "group-a-" . rand(1000,1000000) . time() . "-" . $avatar_name;
                        $upload_name = $upload_dir . strtolower($random_name);
                        $upload_name = preg_replace('/\s+/', '-', $upload_name);

                        if (move_uploaded_file($avatar_tmp_name , $upload_name)) {
                            $avatar = $upload_name;
                            $avatar = str_replace("./","",$avatar);
                        }
                    }

                }
                if (isset($request->only('banner')["banner"])) {

                    // Banner Upload
                    $response = array();
                    $upload_dir = './static/';

                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $banner_name = $_FILES["banner"]["name"];
                    $banner_tmp_name = $_FILES["banner"]["tmp_name"];
                    $error = $_FILES["banner"]["error"];

                    if($error > 0){
                        $response = array(
                            "status" => 0,
                            "error" => true,
                            "message" => "Error uploading the file!"
                        );
                    } else {
                        $random_name = "group-b-" . rand(1000,1000000) . time() . "-" . $banner_name;
                        $upload_name = $upload_dir . strtolower($random_name);
                        $upload_name = preg_replace('/\s+/', '-', $upload_name);

                        if (move_uploaded_file($banner_tmp_name , $upload_name)) {
                            $banner = $upload_name;
                            $banner = str_replace("./","",$banner);
                        }
                    }

                }

                $groupId = DB::table('groups')->insertGetId([
                    'creator_user_id' => $user_id,
                    'group_title' => $title,
                    'group_description' => $description,
                    'group_avatar' => $avatar,
                    'group_banner' => $banner,
                    'group_private' => $private,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);

                DB::table('group_members')->insert([
                    'group_id' => $groupId,
                    'user_id' => $user_id,
                    'is_admin' => 1,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);
                
                DB::table('group_requests')->insert([
                    'group_id' => $groupId,
                    'user_id' => $user_id,
                    'status' => 'approved',
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);

                DB::table('group_tags')->insert([
                    'group_id' => $groupId,
                    'tag' => $title,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);

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

                $hashtagsArray = explode(',', get_hashtags($description));
                if (count($hashtagsArray) > 0) {
                    foreach($hashtagsArray as $hashtag) {

                        $hashtag = trim($hashtag);

                        if ($hashtag !== "") {

                            DB::table('group_tags')->insert([
                                'group_id' => $groupId,
                                'tag' => $hashtag,
                                'created_at' => date('Y-m-d H:i:s', time()),
                                'updated_at' => date('Y-m-d H:i:s', time())
                            ]);

                        }

                    }
                }

                $return = array('status' => 1, 'group_id' => $groupId);

            } else {
                $return = array('status' => 0);
            }
            
            
            return response()->json($return);
        }
    }

    // Edit group
    public function editGroup(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            if (
                isset($request->only('title')["title"]) &&
                isset($request->only('description')["description"]) &&
                isset($request->only('private')["private"]) &&
                isset($request->only('group_id')["group_id"])
            ) {
                $title = $request->only('title')["title"];
                $description = $request->only('description')["description"];
                $private = $request->only('private')["private"];
                $group_id = $request->only('group_id')["group_id"];

                $isAdmin = DB::table('group_members')
                    ->where([
                        ['group_id', $group_id],
                        ['user_id', $user_id],
                        ['is_admin', 1]
                    ])
                    ->get()
                    ->count();

                if ($isAdmin == 0) {
                    $return = array('status' => 0);
                } else {

                    $title = trim($title);
                    $description = trim($description);
    
                    $avatar = "";
                    $banner = "";
    
                    if ($private == "true") {$private = "1";}
                    else {$private = "0";}
                    
                    if (isset($request->only('avatar')["avatar"])) {
                        
                        // Avatar Upload
                        $response = array();
                        $upload_dir = './static/';
    
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
    
                        $avatar_name = $_FILES["avatar"]["name"];
                        $avatar_tmp_name = $_FILES["avatar"]["tmp_name"];
                        $error = $_FILES["avatar"]["error"];
    
                        if($error > 0){
                            $response = array(
                                "status" => 0,
                                "error" => true,
                                "message" => "Error uploading the file!"
                            );
                        } else {
                            $random_name = "group-a-" . rand(1000,1000000) . time() . "-" . $avatar_name;
                            $upload_name = $upload_dir . strtolower($random_name);
                            $upload_name = preg_replace('/\s+/', '-', $upload_name);
    
                            if (move_uploaded_file($avatar_tmp_name , $upload_name)) {
                                $avatar = $upload_name;
                                $avatar = str_replace("./","",$avatar);

                                DB::table('groups')
                                    ->where('id', $group_id)
                                    ->update([
                                        'group_avatar' => $avatar
                                    ]);
                            }
                        }
    
                    }
                    if (isset($request->only('banner')["banner"])) {
    
                        // Banner Upload
                        $response = array();
                        $upload_dir = './static/';
    
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
    
                        $banner_name = $_FILES["banner"]["name"];
                        $banner_tmp_name = $_FILES["banner"]["tmp_name"];
                        $error = $_FILES["banner"]["error"];
    
                        if($error > 0){
                            $response = array(
                                "status" => 0,
                                "error" => true,
                                "message" => "Error uploading the file!"
                            );
                        } else {
                            $random_name = "group-b-" . rand(1000,1000000) . time() . "-" . $banner_name;
                            $upload_name = $upload_dir . strtolower($random_name);
                            $upload_name = preg_replace('/\s+/', '-', $upload_name);
    
                            if (move_uploaded_file($banner_tmp_name , $upload_name)) {
                                $banner = $upload_name;
                                $banner = str_replace("./","",$banner);

                                DB::table('groups')
                                    ->where('id', $group_id)
                                    ->update([
                                        'group_banner' => $banner
                                    ]);
                            }
                        }
    
                    }

                    DB::table('groups')
                        ->where('id', $group_id)
                        ->update([
                            'group_title' => $title,
                            'group_description' => $description,
                            'group_private' => $private,
                            'updated_at' => date('Y-m-d H:i:s', time())
                        ]);

                    if ($private == "0") {
                        $pendingRequests = DB::table('group_requests')
                            ->where([
                                ['group_id', $group_id],
                                ['status', 'pending']
                            ])
                            ->get();

                        foreach ($pendingRequests as $pendingRequest) {

                            DB::table('group_requests')
                                ->where('id', $pendingRequest->id)
                                ->update(['status'=>'approved',"updated_at" => date('Y-m-d H:i:s')]);

                            DB::table('group_members')->insert([
                                'group_id' => $group_id,
                                'user_id' => $pendingRequest->user_id,
                                "created_at" =>  date('Y-m-d H:i:s'),
                                "updated_at" => date('Y-m-d H:i:s'),
                            ]);

                        }
                    }
                    
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
    
                    $hashtagsArray = explode(',', get_hashtags($description));
                    if (count($hashtagsArray) > 0) {
                        foreach($hashtagsArray as $hashtag) {
    
                            $hashtag = trim($hashtag);
    
                            if ($hashtag !== "") {
                                $countTags = DB::table('group_tags')
                                    ->where([
                                        ['tag',$hashtag],
                                        ['group_id',$group_id]
                                    ])
                                    ->get()
                                    ->count();
                                   
                                if ($countTags == 0) {
                                    DB::table('group_tags')->insert([
                                        'group_id' => $group_id,
                                        'tag' => $hashtag,
                                        'created_at' => date('Y-m-d H:i:s', time()),
                                        'updated_at' => date('Y-m-d H:i:s', time())
                                    ]);
                                }
                            }
    
                        }
                    }
    
                    $return = array('status' => 1);
                }

            } else {
                $return = array('status' => 0);
            }
            
            
            return response()->json($return);
        }
    }

    // Get all members
    public function getAllMembers(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            if (
                isset($request->only('group_id')["group_id"])
            ) {
                $group_id = $request->only('group_id')["group_id"];

                $groupMembers = DB::table('group_members')
                    ->where([
                        ['group_id', $group_id]
                    ])
                    ->get()
                    ->toArray();

                if (count($groupMembers) == 0) {
                    $return = array('status' => 0);
                } else {

                    forEach($groupMembers as $groupMember) {
                        $groupMemberUser = DB::table('users')
                            ->select('account_activated', 'avatar', 'banner', 'email', 'id', 'name', 'user_last_online', 'user_online')
                            ->where([
                                ['id', $groupMember->user_id]
                            ])
                            ->get()
                            ->toArray();

                        if (count($groupMemberUser) > 0) {
                            $groupMember->user = $groupMemberUser[0];
                        }
                    }
    
                    $return = array('status' => 1,'users' => $groupMembers);

                }

            } else {
                $return = array('status' => 0);
            }
            
            
            return response()->json($return);
        }
    }

    // Get all requests
    public function getAllRequests(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            if (
                isset($request->only('group_id')["group_id"])
            ) {
                $group_id = $request->only('group_id')["group_id"];

                $isAdmin = DB::table('group_members')
                    ->where([
                        ['group_id', $group_id],
                        ['user_id', $user_id],
                        ['is_admin', 1]
                    ])
                    ->get()
                    ->count();

                if ($isAdmin == 0) {
                    $return = array('status' => 0);
                } else {

                    $allRequests = DB::table('group_requests')
                        ->where([
                            ['group_id', $group_id],
                            ['status', 'pending']
                        ])
                        ->get()
                        ->toArray();
    
                    $return = array('status' => 1,'requests' => $allRequests);
                }

            } else {
                $return = array('status' => 0);
            }
            
            
            return response()->json($return);
        }
    }

    // Get request
    public function getRequest(Request $request) {
        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            if (isset($request->only('request_id')["request_id"])) {

                $request_id = $request->only('request_id')["request_id"];

                $return = array();

                // Get Request
                $group_request = DB::table('group_requests')
                    ->where('id', $request_id)
                    ->get();
                $group_request = $group_request[0];

                // Get Group
                $group = DB::table('groups')
                    ->where('id', $group_request->group_id)
                    ->get();
                $group = $group[0];

                // Get my membership
                $group_membership = DB::table('group_members')
                    ->where([
                        ['group_id', $group_request->group_id],
                        ['user_id', $user_id]
                    ])
                    ->get();
                $group_membership = $group_membership[0];

                // Get the user of the request
                $request_user = DB::table('users')
                    ->select('id', 'name', 'avatar', 'email', 'user_online')
                    ->where('id', $group_request->user_id)
                    ->get();
                $request_user = $request_user[0];

                $return = array(
                    'request' => $group_request,
                    'group' => $group,
                    'group_membership' => $group_membership,
                    'request_user' => $request_user
                );

                return response()->json($return); 

            }

        }
    }

    // Update Request Status
    public function updateRequestStatus(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {
            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            if (isset($request->only('request_id')["request_id"]) && isset($request->only('new_status')["new_status"])) {

                $request_id = $request->only('request_id')["request_id"];
                $new_status = $request->only('new_status')["new_status"];
                
                $return = array();

                if ($new_status !== "approved" && $new_status !== "rejected" && $new_status !== "cancelled") {
                    return response()->json(array('status' => 0));  
                }

                // Get Request
                $group_request = DB::table('group_requests')
                    ->where('id', $request_id)
                    ->get();
                $group_request = $group_request[0];

                // Get Group
                $group = DB::table('groups')
                    ->where('id', $group_request->group_id)
                    ->get();
                $group = $group[0];

                // Get my membership
                $group_membership = DB::table('group_members')
                    ->where([
                        ['group_id', $group_request->group_id],
                        ['user_id', $user_id]
                    ])
                    ->get();

                if (count($group_membership) == 0) {
                    $group_membership = array(array('is_admin'=>false));
                }

                $group_membership = $group_membership[0];

                // Get the user of the request
                $request_user = DB::table('users')
                    ->select('id', 'name', 'avatar', 'email', 'user_online')
                    ->where('id', $group_request->user_id)
                    ->get();
                $request_user = $request_user[0];

                if ($group_request->status !== "pending") {
                    return response()->json(array('status' => 0));
                }

                // Allow user himself to cancel the request
                if ($new_status == "cancelled") {

                    if ($user_id == $request_user->id) {
                        DB::table('group_requests')
                            ->where('id', $request_id)
                            ->update(['status'=>'cancelled','updated_at' => date('Y-m-d H:i:s')]);
                        
                        $return = array('status' => 1);
                    } else {
                        $return = array('status' => 0);
                    }

                } else {

                    if ($new_status == "approved") {
                        Mail::send(new GroupRequestApprovedMail($request_user->email, env("APP_URL"), $request_user->name, $request_user->email, $group->group_title));
                    }
                    if ($new_status == "rejected") {
                        Mail::send(new GroupRequestRejectedMail($request_user->email, env("APP_URL"), $request_user->name, $request_user->email, $group->group_title));
                    }

                    if ($group_membership->is_admin == 1) {

                        DB::table('group_requests')
                            ->where('id', $request_id)
                            ->update(['status'=> $new_status, 'updated_at' => date('Y-m-d H:i:s')]);

                        if ($new_status == "approved") {

                            DB::table('group_members')->insert([
                                'group_id' => $group_request->group_id,
                                'user_id' => $request_user->id,
                                "created_at" =>  date('Y-m-d H:i:s'),
                                "updated_at" => date('Y-m-d H:i:s'),
                            ]);

                        }
                        
                        $return = array('status' => 1);

                    }

                }

                return response()->json($return); 

            }

        }

    }

}
