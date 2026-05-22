<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

/**
 * Canal 'push' para notificaciones Laravel.
 * Delega el envío al método toPush() de la notificación.
 */
class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toPush')) {
            return;
        }

        if (method_exists($notification, 'viaPush') && ! $notification->viaPush()) {
            return;
        }

        $notification->toPush($notifiable);
    }
}
