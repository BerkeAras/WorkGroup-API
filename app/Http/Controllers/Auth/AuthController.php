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
use App\Mail\RegisterActivationMail;
use Exception;
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

        // Check if user is activated
        $user = DB::table("users")
            ->where("email", $request->only('email')["email"])
            ->get();
        $user = $user[0];

        if ($user->account_activated == 0) {
            return $this->onUnauthorized();
        }

        DB::table("users")
            ->where("email", $request->only('email')["email"])
            ->update([
                'user_online' => "1",
                'user_last_online' => date('Y-m-d H:i:s', time())
            ]);

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
    protected function validatePostReset2Request(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255',
            'pin' => 'required|max:6',
            'token' => 'required'
        ]);
    }
    
    protected function validatePostReset3Request(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255',
            'token' => 'required',
            'pin' => 'required|max:6',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8'
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
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8'
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
        $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

        DB::table('users')->where('id', $user_id)->update(['user_online'=>0]);

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
                $token = str_random(20) . md5($email);

                $user_id = DB::table('users')->insertGetId([
                    'name' => $name,
                    'email' => $email,
                    'password' => app('hash')->make($password),
                    'remember_token' => str_random(10),
                    'activation_token' => $token,
                    'account_activated' => false,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);

                DB::table('user_information')->insert([
                    'user_id' => $user_id,
                    'user_slogan' => "$name's Account!",
                    "created_at" =>  date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);

                try {
                    Mail::send(new RegisterActivationMail($email, env("APP_URL"), $token));
                
                    return response([
                        'message' => 'Register success'
                    ]);
                } catch (Exception $ex) {
                    // Debug via $ex->getMessage();
                    return response([
                        'message' => 'Register error'
                    ]);
                }
    
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

            function generateNumericOTP($n) { 
      
                // Take a generator string which consist of 
                // all numeric digits 
                $generator = "1357902468"; 
              
                // Iterate for n-times and pick a single character 
                // from generator and append it to $result 
                  
                // Login for generating a random character from generator 
                //     ---generate a random number 
                //     ---take modulus of same with length of generator (say i) 
                //     ---append the character at place (i) from generator to result 
              
                $result = ""; 
              
                for ($i = 1; $i <= $n; $i++) { 
                    $result .= substr($generator, (rand()%(strlen($generator))), 1); 
                } 
              
                // Return result 
                return $result; 
            }

            $otp = generateNumericOTP(6);

            DB::table('user_reset')->insert([
                'user_id' => $results->id,
                'email' => $results->email,
                'token' => $token,
                'otp' => $otp,
                'status' => "0"
            ]);

            try {
                Mail::send(new PasswordResetMail($results->email, $otp));
            
                return response([
                    'message' => 'Reset success',
                    'token' => $token
                ]);
            } catch (Exception $ex) {
                // Debug via $ex->getMessage();
                return response([
                    'message' => 'Reset error'
                ]);
            }

        }
    }

    public function postReset2(Request $request) {
        try {
            $this->validatePostReset2Request($request);
        } catch (HttpResponseException $e) {
            return $this->onBadRequest();
        }

        $email = $request->only('email')["email"];
        $token = $request->only('token')["token"];
        $pin = $request->only('pin')["pin"];

        $results = DB::table("user_reset")->where("email", $email)->where("token", $token)->where("otp", $pin)->where("status","0")->first();
        $count = DB::table("user_reset")->where("email", $email)->where("token", $token)->where("otp", $pin)->where("status","0")->count();
        if ($count == 0) {
            return response([
                'message' => 'PIN incorrect'
            ]);
        } else {

            return response([
                'message' => 'PIN correct'
            ]);

        }
    } 
    
    public function postReset3(Request $request) {
        try {
            $this->validatePostReset3Request($request);
        } catch (HttpResponseException $e) {
            return $this->onBadRequest();
        }

        $email = $request->only('email')["email"];
        $token = $request->only('token')["token"];
        $pin = $request->only('pin')["pin"];
        $password = $request->only('password')["password"];
        $password_confirmation = $request->only('password_confirmation')["password_confirmation"];

        if ($password !== $password_confirmation) {
            return response([
                'message' => 'Passwords does not match'
            ]);
        } else {

            $results = DB::table("user_reset")->where("email", $email)->where("token", $token)->where("otp", $pin)->where("status","0")->first();
            $count = DB::table("user_reset")->where("email", $email)->where("token", $token)->where("otp", $pin)->where("status","0")->count();
            if ($count == 0) {
                return response([
                    'message' => 'PIN incorrect'
                ]);
            } else {
    
                DB::table("user_reset")
                    ->where("email", $email)
                    ->where("token", $token)
                    ->where("otp", $pin)
                    ->where("status","0")
                    ->update(['status' => "1"]);
                
                DB::table("users")
                    ->where("email", $email)
                    ->update(['password' => app('hash')->make($password)]);
    
                return response([
                    'message' => 'Password resetted'
                ]);
    
            }

        }

    } 

    public function activity(Request $request) {

        if (JWTAuth::parseToken()->authenticate()) {

            $user_id = json_decode(JWTAuth::parseToken()->authenticate(), true)["id"];

            DB::table("users")
                ->where("id", $user_id)
                ->update([
                    'user_online' => "1",
                    'user_last_online' => date('Y-m-d H:i:s', time())
                ]);

            $token = JWTAuth::parseToken();
            $newToken = $token->refresh();

            return response([
                'message' => 'Activity updated',
                'token' => $newToken
            ]);

        }

    }

    public function checkActivation(Request $request) {

        $email = $request->only('email')["email"];

        if ($email) {
            $user = DB::table("users")
                ->where("email", $email)
                ->where("account_activated", 1)
                ->get();

            if (count($user) == 1) {
                return response([
                    'message' => 'User activated'
                ]);
            } else {
                return response([
                    'message' => 'User not activated'
                ]);
            }
        } else {
            return response([
                'message' => 'No email provided'
            ]);
        }

    }

    public function activate(Request $request) {

        $token = $request->only('token')["token"];

        if ($token) {
            $updateUser = DB::table("users")
                ->where("activation_token", $token)
                ->update([
                    'account_activated' => 1
                ]);

            if ($updateUser == 1) {
                return response([
                    'error' => false,
                    'message' => 'User activated'
                ]);
            } else {
                return response([
                    'error' => true,
                    'message' => 'Token invalid'
                ]);
            }
        } else {
            return response([
                'error' => true,
                'message' => 'No token provided'
            ]);
        }

    }
}
