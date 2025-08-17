<?php
namespace App\Helpers;

use App\Models\Notification;

class NotifyHelper
{
    public static function notify($user_id, $title, $body = null)
    {
        return Notification::create([
            'user_id' => $user_id,
            'title'   => $title,
            'body'    => $body,
            'sent_at' => now()
        ]);
    }
}
