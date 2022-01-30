<?php

namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class NotificationMail extends Mailable {
 
    use Queueable,
        SerializesModels;

    public $toAddress;
    public $workgroup_url;
    public $notification_sender_name;
    public $notification_recipient_name;
    public $notification_subject;
    public $notification_content;

    public function __construct($to, $workgroup_url, $notification_sender_name, $notification_recipient_name, $notification_subject, $notification_content)
    {
        $this->toAddress = $to;
        $this->workgroup_url = $workgroup_url;
        $this->notification_sender_name = $notification_sender_name;
        $this->notification_recipient_name = $notification_recipient_name;
        $this->notification_subject = $notification_subject;
        $this->notification_content = $notification_content;
    }

    //build the message.
    public function build() {

        return $this->to($this->toAddress)
        ->subject($this->notification_subject . ' – WorkGroup')
        ->view('notification-mail');

    }
}