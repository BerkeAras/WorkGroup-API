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
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\NotificationMailJob;

class NotificationController extends Controller
{
    // Sends notifications to user
    public function sendNotification(
        $notification_recipient_user_id,
        $notification_sender_user_id,
        $notification_subject,
        $notification_content,
        $notification_link,
        $notification_type
    )
    {
        // Get the user to send the notification to
        $user = DB::table('users')->where('id', $notification_recipient_user_id)->first();

        // Add the notification to the database
        DB::table('notifications')->insert([
            'notification_recipient_user_id' => $notification_recipient_user_id,
            'notification_sender_user_id' => $notification_sender_user_id,
            'notification_subject' => $notification_subject,
            'notification_content' => $notification_content,
            'notification_link' => $notification_link,
            'notification_type' => $notification_type,
            'notification_delivery_type' => $user->notification_delivery_type,
            'notification_read' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Sender Name
        if ($notification_sender_user_id == 0) {
            $sender_name = "WorkGroup";
        } else {
            $sender = DB::table('users')->where('id', $notification_sender_user_id)->first();
            $sender_name = $sender->name;
        }

        // Send the notification
        if ($user->notification_delivery_type == 'email') {
            // Send the email
            $this->sendEmail($user->email, $notification_subject, $notification_content, $sender_name, $user->name);
        }
    }

    // Sends an email
    public function sendEmail($email, $subject, $content, $sender_name, $recipient_name)
    {
        // Send the email

        $details = [
            'email' => $email,
            'sender_name' => $sender_name,
            'recipient_name' => $recipient_name,
            'subject' => $subject,
            'content' => $content,
        ];

        dispatch(new NotificationMailJob($details));
    }

    // Gets the notifications for a user
    public function getNotifications()
    {

        $user_id = JWTAuth::parseToken()->authenticate()->id;

        // Get the notifications for the user
        $notifications = DB::table('notifications')->where('notification_recipient_user_id', $user_id)->orderBy('created_at', 'desc')->get();

        // Return the notifications
        return $notifications;
    }

    // Gets the in-app notifications for a user
    public function getInAppNotifications()
    {

        $user_id = JWTAuth::parseToken()->authenticate()->id;

        if (isset($_GET["select"])) {
            if (is_numeric($_GET["select"])) {
                $select = $_GET["select"];
            } else {
                $select = 10;
            }

            $notifications = DB::table('notifications')
                                ->where('notification_recipient_user_id', $user_id)
                                ->where('notification_delivery_type', 'inapp')
                                ->orderBy('created_at', 'desc')
                                ->take($select)
                                ->get()
                                ->toArray();
        } else {
            $notifications = DB::table('notifications')
                                ->where('notification_recipient_user_id', $user_id)
                                ->where('notification_delivery_type', 'inapp')
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->toArray();
        }

        // Get the user details to the notification

        foreach ($notifications as $notification) {
            if($notification->notification_sender_user_id != 0) {
                $user = DB::table('users')->where('id', $notification->notification_sender_user_id)->first();
                $notification->user = $user;
            }

            if (isset($_GET["setread"])) {
                DB::table('notifications')->where('id', $notification->id)->update(['notification_read' => true]);
            }
        }

        // Return the notifications
        return $notifications;
    }

    // Check unread notifications
    public function checkUnreadNotifications()
    {
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        $unreadNotifications = DB::table('notifications')->where('notification_recipient_user_id', $user_id)->where('notification_delivery_type','inapp')->where('notification_read', false)->count();

        return $unreadNotifications;
    }
}
