<?php

namespace App\Notifications\Concerns;

use App\Services\PushService;

/**
 * Trait que agrega el canal 'push' a una notificación
 * y delega el envío a PushService.
 *
 * Uso: en la notificación implementar pushTitle() y pushBody().
 */
trait SendsPushNotification
{
    public function viaPush(): bool
    {
        // Siempre intentar — PushService detecta el tipo de token (Expo vs FCM)
        // y usa la API correcta. FCM requiere Firebase config; Expo no.
        return true;
    }

    /**
     * Se llama cuando el canal es 'push'.
     * Usa el notifiable (User) para buscar sus device tokens.
     */
    public function toPush(object $notifiable): void
    {
        PushService::sendToUser(
            $notifiable,
            $this->pushTitle(),
            $this->pushBody(),
            $this->pushData(),
        );
    }

    abstract protected function pushTitle(): string;
    abstract protected function pushBody(): string;

    protected function pushData(): array
    {
        return [];
    }
}
