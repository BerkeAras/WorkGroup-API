<?php

    namespace App\Http\Controllers;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Mail;
    use App\Mail\PasswordResetMail;
    
    class MailController extends Controller
    {
        public $toAddress;
        public $token;
        
        public function __construct($to, $token)
        {
            $this->toAddress = $to;
            $this->token = $token;
        }
    
        public function send(Request $request)
        {
            return $this->to($this->toAddress)
            ->subject('Example!')
            ->view('password-reset-mail');
        }
    }