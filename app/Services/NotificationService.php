<?php
namespace App\Services;
use App\Models\User;
use App\Models\AppNotification;

class NotificationService
{
    public function send(User $user, $title, $message, $data = [])
    {
        // سجل في DB
        AppNotification::create([
            'user_id'=>$user->id,
            'title'=>$title,
            'message'=>$message,
            'data'=>$data
        ]);
        // أرسل FCM إذا كان لديه fcm_token
        // استخدم Http client أو حزمة FCM
    }
}
