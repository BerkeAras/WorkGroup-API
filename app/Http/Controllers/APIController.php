<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class APIController extends Controller
{
    /**
     * Get root url.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex(Application $app)
    {
        return response(['message' => $app->version()]);
    }

    /**
     * Check Database connection status
     *
     * @return \Illuminate\Http\Response
     */
    public function checkConnection() {
        try {
            DB::connection()->getPdo();
            return "OK";
        } catch (\Exception $e) {
            return "ERROR";
        }
    }
}
