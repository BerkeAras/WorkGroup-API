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
            
            DB::table('group_requests')->insert([
                'group_id' => $group_id,
                'user_id' => $user_id,
                'status' => 'pending',
                "created_at" =>  date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);

            $group = DB::table('group')
                ->where('id', $group_id)
                ->get()
                ->toArray();

            if ($group->group_private == 1) {
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

}
