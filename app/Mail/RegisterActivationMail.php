<?php

namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class RegisterActivationMail extends Mailable {
 
    use Queueable,
        SerializesModels;

    public $toAddress;
    public $workgroup_url;
    public $activation_token;
    
    public function __construct($to, $workgroup_url, $activation_token)
    {
        $this->toAddress = $to;
        $this->workgroup_url = $workgroup_url;
        $this->activation_token = $activation_token;
    }
 
    //build the message.
    public function build() {

        return $this->to($this->toAddress)
        ->subject('Activate your WorkGroup Account')
        ->view('register-activation-mail');

    }
}