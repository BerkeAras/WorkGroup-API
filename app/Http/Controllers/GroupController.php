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

                $isGroupMember = DB::table('group_members')
                    ->where('user_id', $user_id)
                    ->count();

                $return = $group_information[0];
                $return->members = $group_members;
                $return->member_count = count($group_members);

                if ($isGroupMember > 0) {
                    $return->is_group_member = true;
                } else {
                    $return->is_group_member = false;
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
                foreach ($group_requests as $group_request) {
                    if ($group_request->status == "pending") {
                        $group->hasAlreadyRequested = true;
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

            $group_tags = array_unique($group_tags);

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
                DB::table('group_requests')->insert([
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'status' => 'pending',
                    "created_at" =>  date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);

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
                $avatar = "";
                $banner = "";

                if ($private == "true") {$private = "1";}
                else {$private = "0";}

                if (isset($request->only('avatar')["avatar"])) {
                    $avatar = $request->only('avatar')["avatar"];
                }
                if (isset($request->only('banner')["banner"])) {
                    $banner = $request->only('banner')["banner"];
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

}
