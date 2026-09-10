<?php

namespace App\Filament\Pages;

use App\Models\Configuracion;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AppMovilSettings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'App móvil';
    protected static ?string $title           = 'App móvil — Tiendas de descarga';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 13;
    protected string $view = 'filament.pages.app-movil-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Genéricas mientras la app no esté publicada en las tiendas —
            // actualizar aquí en cuanto se suba a producción.
            'app_store_url'  => setting('app_store_url', 'https://google.com.mx'),
            'play_store_url' => setting('play_store_url', 'https://google.com.mx'),
            'screenshot_login_ios'      => setting('screenshot_login_ios'),
            'screenshot_cliente_android' => setting('screenshot_cliente_android'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Enlaces de descarga')
                    ->description('URLs que se muestran en la página pública de descarga de la app. Mientras la app no esté publicada, deja una URL genérica (ej. https://google.com.mx) para que los botones no queden rotos; actualízalas en cuanto la app esté disponible en cada tienda.')
                    ->schema([
                        Forms\Components\TextInput::make('app_store_url')
                            ->label('App Store (iOS)')
                            ->url()
                            ->required()
                            ->prefix('🍎')
                            ->placeholder('https://apps.apple.com/mx/app/tu-app/idXXXXXXXXX')
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'La URL de App Store es obligatoria.',
                                'url'      => 'Debe ser una URL válida.',
                            ]),

                        Forms\Components\TextInput::make('play_store_url')
                            ->label('Google Play (Android)')
                            ->url()
                            ->required()
                            ->prefix('🤖')
                            ->placeholder('https://play.google.com/store/apps/details?id=com.tu.app')
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'La URL de Google Play es obligatoria.',
                                'url'      => 'Debe ser una URL válida.',
                            ]),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Capturas de pantalla')
                    ->description('Se muestran en los mockups de teléfono de la página pública de descarga. Sube una captura vertical de pantalla completa (relación ~9:19); si dejas alguna vacía, se usa la más reciente ya publicada.')
                    ->schema([
                        Forms\Components\FileUpload::make('screenshot_login_ios')
                            ->label('Captura — iPhone (pantalla de acceso)')
                            ->image()
                            ->imagePreviewHeight('280')
                            ->disk('public')
                            ->directory('site/app-screenshots')
                            ->helperText('Formato PNG o JPG. Se recorta desde arriba dentro del marco del teléfono.'),

                        Forms\Components\FileUpload::make('screenshot_cliente_android')
                            ->label('Captura — Android (portal del cliente)')
                            ->image()
                            ->imagePreviewHeight('280')
                            ->disk('public')
                            ->directory('site/app-screenshots')
                            ->helperText('Formato PNG o JPG. Se recorta desde arriba dentro del marco del teléfono.'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $clave => $valor) {
            Configuracion::set($clave, $valor);
        }

        foreach (array_keys($state) as $clave) {
            Cache::forget("config_{$clave}");
        }

        Notification::make()
            ->title('URLs de las tiendas guardadas correctamente.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
