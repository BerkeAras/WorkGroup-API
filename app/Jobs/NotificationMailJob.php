<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;

class NotificationMailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = new NotificationMail(
            $this->details['email'],
            env("APP_URL"),
            $this->details['sender_name'],
            $this->details['recipient_name'],
            $this->details['subject'] . '___',
            $this->details['content']
        );
        Mail::to($this->details['email'])->send($email);
    }
}