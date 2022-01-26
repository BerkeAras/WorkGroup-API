<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class JobsController extends Controller
{
    // Executes onlineStatus Job
    public function onlineStatus()
    {

        $users = DB::table('users')
            ->get();

        foreach ($users as $user) {
            $user_information = DB::table('user_information')
                ->where('user_id', $user->id)
                ->get();

            $user->user_information = array();

            if (count($user_information) != 0 && $user_information[0]) {
                $user_information[0]->time_diff = time() - strtotime($user->user_last_online);
                if ($user_information[0]->time_diff > 300) {
                    // Inactive for 5 minutes
                    // Set Offline

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'user_online' => 0,
                        ]);
                }
            }

        }

        return Response::make(array('executed_job' => 'onlineStatus'), 200);
    }
}
