<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use App\Http\Controllers\MailController;

class AuthController extends Controller
{
    /**
     * Handle a login request to the application.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        try {
            $this->validatePostLoginRequest($request);
        } catch (HttpResponseException $e) {
            return $this->onBadRequest();
        }

        try {
            // Attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt(
                $this->getCredentials($request)
            )) {
                return $this->onUnauthorized();
            }
        } catch (JWTException $e) {
            // Something went wrong whilst attempting to encode the token
            return $this->onJwtGenerationError();
        }

        // All good so return the token
        return $this->onAuthorized($token);
    }

    /**
     * Validate authentication request.
     * @param  Request $request
     * @return void
     * @throws HttpResponseException
     */
    protected function validatePostLoginRequest(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required',
        ]);
    }

    /**
     * Validate authentication request.
     * @param  Request $request
     * @return void
     * @throws HttpResponseException
     */
    protected function validatePostResetRequest(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255'
        ]);
    }
    
    /**
     * Validate register request.
     * @param  Request $request
     * @return void
     * @throws HttpResponseException
     */
    protected function validatePostRegisterRequest(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required',
            'password_confirmation' => 'required'
        ]);
    }

    /**
     * What response should be returned on bad request.
     * @return Response
     */
    protected function onBadRequest()
    {
        return response([
            'message' => 'Invalid credentials'
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * What response should be returned on invalid credentials.
     * @return Response
     */
    protected function onUnauthorized()
    {
        return response([
            'message' => 'Invalid credentials'
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * What response should be returned on error while generate JWT.
     * @return Response
     */
    protected function onJwtGenerationError()
    {
        return response([
            'message' => 'Could not create token'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * What response should be returned on authorized.
     * @return Response
     */
    protected function onAuthorized($token)
    {
        return response([
            'message' => 'Login success',
            'data' => [
                'token' => $token,
            ]
        ]);
    }

    /**
     * Get the needed authorization credentials from the request.
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getCredentials(Request $request)
    {
        return $request->only('email', 'password');
    }

    /**
     * Invalidate a token.
     * @return \Illuminate\Http\Response
     */
    public function deleteInvalidate()
    {
        $token = JWTAuth::parseToken();
        $token->invalidate();
        return response(['message' => 'Token invalidated']);
    }

    /**
     * Refresh a token.
     * @return \Illuminate\Http\Response
     */
    public function patchRefresh()
    {
        $token = JWTAuth::parseToken();
        $newToken = $token->refresh();
        return response([
            'message' => 'Token refreshed',
            'data' => [
                'token' => $newToken
            ]
        ]);
    }

    /**
     * Get authenticated user.
     * @return \Illuminate\Http\Response
     */
    public function getUser()
    {
        return response([
            'message' => 'Authenticated user',
            'data' => JWTAuth::parseToken()->authenticate()
        ]);
    }

    /**
     * Handles a register request.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        try {
            $this->validatePostRegisterRequest($request);
        } catch (HttpResponseException $e) {
            return $this->onBadRequest();
        }

        if ($request->only('password')["password"] !== $request->only('password_confirmation')["password_confirmation"]) {
            return $this->onBadRequest();
        } else {

            $name = $request->only('name')["name"];
            $email = $request->only('email')["email"];
            $password = $request->only('password')["password"];

            $results = DB::select("SELECT * FROM users WHERE email = '$email'");
            if (count($results) == 0) {
                DB::table('users')->insert([
                    'name' => $name,
                    'email' => $email,
                    'password' => app('hash')->make($password),
                    'remember_token' => str_random(10),
                ]);
    
                return response([
                    'message' => 'Register success'
                ]);
            } else {
                return response([
                    'message' => 'User existing'
                ]);
            }
            
        }
    }

    /**
     * Handles a reset request.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postReset(Request $request)
    {
        try {
            $this->validatePostResetRequest($request);
        } catch (HttpResponseException $e) {
            return $this->onBadRequest();
        }

        $email = $request->only('email')["email"];

        $results = DB::table("users")->where("email", $email)->first();
        $count = DB::table("users")->where("email", $email)->count();
        if ($count == 0) {
            return response([
                'message' => 'User not existing'
            ]);
        } else {

            $token = str_random(20) . md5($results->id);

            DB::table('user_reset')->insert([
                'user_id' => $results->id,
                'email' => $results->email,
                'token' => $token
            ]);

            Mail::send(new PasswordResetMail($results->email, $token));
        }
    }
}
