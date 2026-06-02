<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Console\Command;

class TestPushNotification extends Command
{
    protected $signature = 'push:test
                            {email? : Correo del usuario (por defecto alainttlm@gmail.com)}
                            {--title= : Título de la notificación}
                            {--body= : Cuerpo de la notificación}';

    protected $description = 'Envía una push notification de prueba a todos los dispositivos de un usuario.';

    public function handle(): int
    {
        $email = $this->argument('email') ?? 'alainttlm@gmail.com';
        $title = $this->option('title') ?? '🔔 Notificación de prueba';
        $body  = $this->option('body')  ?? 'Si ves esto, las push notifications funcionan correctamente.';

        // Buscar usuario
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Usuario con email [{$email}] no encontrado.");
            return self::FAILURE;
        }

        $this->info("Usuario: {$user->name} ({$user->email})");

        // Verificar tokens
        $tokens = DeviceToken::where('user_id', $user->id)->get();

        if ($tokens->isEmpty()) {
            $this->warn('El usuario no tiene dispositivos registrados (ningún FCM token).');
            $this->line('Asegúrate de haber iniciado sesión en la app al menos una vez.');
            return self::FAILURE;
        }

        $this->info("Dispositivos registrados: {$tokens->count()}");

        foreach ($tokens as $token) {
            $this->line("  → Plataforma: {$token->plataforma} | Token: " . substr($token->fcm_token, 0, 30) . '...');
        }

        $this->newLine();

        // Enviar
        $this->info("Enviando push notification...");
        $this->line("  Título : {$title}");
        $this->line("  Mensaje: {$body}");
        $this->newLine();

        PushService::sendToUser($user, $title, $body, [
            'tipo'   => 'test',
            'origen' => 'artisan:push:test',
        ]);

        $this->info('✓ Notificación enviada. Revisa el dispositivo y los logs de Laravel si no llega.');
        $this->line('  Logs: storage/logs/laravel.log');

        return self::SUCCESS;
    }
}
