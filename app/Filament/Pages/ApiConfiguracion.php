<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ApiConfiguracion extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configuración API';
    protected static ?string $title           = 'Configuración de la API Móvil';
    protected static ?string $navigationGroup = 'API Móvil';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $slug            = 'api-configuracion';

    protected static string $view = 'filament.pages.api-configuracion';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function mount(): void
    {
        $this->form->fill([
            'api_base_url'          => config('app.url') . '/api',
            'api_version'           => 'v1',
            'token_expiration_days' => 30,
            'fcm_server_key'        => config('services.firebase.server_key', ''),
            'apns_key_id'           => config('services.apns.key_id', ''),
            'apns_team_id'          => config('services.apns.team_id', ''),
            'offline_sync_enabled'  => true,
            'gps_tracking_enabled'  => true,
            'max_doc_size_mb'       => 10,
            'allowed_doc_types'     => ['pdf', 'jpg', 'jpeg', 'png', 'heic'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->icon('heroicon-o-globe-alt')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('api_base_url')
                            ->label('URL base de la API')
                            ->disabled()
                            ->helperText('URL que la app móvil usará para conectarse')
                            ->suffixIcon('heroicon-o-link'),

                        Forms\Components\TextInput::make('api_version')
                            ->label('Versión actual')
                            ->disabled()
                            ->helperText('Prefijo de rutas: /api/v1/...'),

                        Forms\Components\TextInput::make('token_expiration_days')
                            ->label('Expiración de tokens (días)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->helperText('Días de validez del token Sanctum para la app'),
                    ]),

                Forms\Components\Section::make('Push Notifications — Firebase (FCM)')
                    ->icon('heroicon-o-bell')
                    ->description('Configuración para notificaciones push en Android y iOS')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('fcm_server_key')
                            ->label('FCM Server Key')
                            ->password()
                            ->revealable()
                            ->helperText('Se obtiene en Firebase Console → Configuración del proyecto → Cloud Messaging'),

                        Forms\Components\Placeholder::make('fcm_info')
                            ->label('')
                            ->content(
                                '1. Crea un proyecto en firebase.google.com' . PHP_EOL .
                                '2. Agrega apps iOS y Android' . PHP_EOL .
                                '3. Descarga google-services.json (Android) y GoogleService-Info.plist (iOS)' . PHP_EOL .
                                '4. Copia la Server Key aquí'
                            ),
                    ]),

                Forms\Components\Section::make('Push Notifications — Apple (APNs)')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->description('Requerido para notificaciones en iOS')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('apns_key_id')
                            ->label('APNs Key ID')
                            ->helperText('10 caracteres — se obtiene en Apple Developer → Certificates'),

                        Forms\Components\TextInput::make('apns_team_id')
                            ->label('Team ID')
                            ->helperText('10 caracteres — en Apple Developer → Membership'),
                    ]),

                Forms\Components\Section::make('Funcionalidades móviles')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('offline_sync_enabled')
                            ->label('Sincronización offline')
                            ->helperText('Permite guardar datos sin internet y sincronizar después'),

                        Forms\Components\Toggle::make('gps_tracking_enabled')
                            ->label('Registro de ubicación GPS')
                            ->helperText('Registra ubicación de visitas a clientes y propiedades'),

                        Forms\Components\TextInput::make('max_doc_size_mb')
                            ->label('Tamaño máx. documento (MB)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->suffix('MB'),

                        Forms\Components\CheckboxList::make('allowed_doc_types')
                            ->label('Tipos de archivo permitidos')
                            ->options([
                                'pdf'  => 'PDF',
                                'jpg'  => 'JPG',
                                'jpeg' => 'JPEG',
                                'png'  => 'PNG',
                                'heic' => 'HEIC (iPhone)',
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Tokens Sanctum activos')
                    ->icon('heroicon-o-key')
                    ->description('Tokens de acceso activos de la app móvil')
                    ->schema([
                        Forms\Components\Placeholder::make('tokens_activos')
                            ->label('')
                            ->content(function () {
                                if (! \Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
                                    return 'La tabla de tokens aún no existe. Se creará al instalar Laravel Sanctum (Fase 1 del desarrollo de la API).';
                                }

                                $tokens = DB::table('personal_access_tokens')
                                    ->orderByDesc('last_used_at')
                                    ->limit(10)
                                    ->get(['name', 'tokenable_id', 'last_used_at', 'created_at', 'expires_at']);

                                if ($tokens->isEmpty()) {
                                    return 'No hay tokens activos aún. Se generarán cuando los asesores inicien sesión desde la app.';
                                }

                                $rows = $tokens->map(fn ($t) =>
                                    "• [{$t->name}] Usuario ID {$t->tokenable_id} — último uso: " .
                                    ($t->last_used_at ?? 'nunca') .
                                    ($t->expires_at ? " — expira: {$t->expires_at}" : '')
                                )->implode(PHP_EOL);

                                return $rows;
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    public function revocarTodosLosTokens(): void
    {
        DB::table('personal_access_tokens')->delete();

        Notification::make()
            ->title('Tokens revocados')
            ->body('Todos los tokens de la app han sido revocados. Los asesores deberán volver a iniciar sesión.')
            ->warning()
            ->send();
    }
}
