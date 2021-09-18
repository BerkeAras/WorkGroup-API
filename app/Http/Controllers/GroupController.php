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

}
