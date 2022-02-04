<?php

namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class PasswordResetMail extends Mailable {
 
    use Queueable,
        SerializesModels;

    public $toAddress;
    public $code;
    public $workgroup_url;
    
    public function __construct($to, $workgroup_url, $code)
    {
        $this->toAddress = $to;
        $this->workgroup_url = $workgroup_url;
        $this->code = $code;
    }
 
    //build the message.
    public function build() {

        return $this->to($this->toAddress)
        ->subject('Reset your WorkGroup-Password')
        ->view('password-reset-mail');

    }
}