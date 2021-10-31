<?php

namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class GroupRequestApprovedMail extends Mailable {
 
    use Queueable,
        SerializesModels;

    public $toAddress;
    public $workgroup_url;
    public $user_name;
    public $user_email;
    public $group_title;
    
    public function __construct($to, $workgroup_url, $user_name, $user_email, $group_title)
    {
        $this->toAddress = $to;
        $this->workgroup_url = $workgroup_url;
        $this->user_name = $user_name;
        $this->user_email = $user_email;
        $this->group_title = $group_title;
    }
 
    //build the message.
    public function build() {

        return $this->to($this->toAddress)
        ->subject('Updates on your group request!')
        ->view('group-request-approved-mail');

    }
}