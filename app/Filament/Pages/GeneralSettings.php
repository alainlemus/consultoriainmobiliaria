<?php

namespace App\Filament\Pages;

use App\Models\Cobertura;
use App\Models\Configuracion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class GeneralSettings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'General';
    protected static ?string $title           = 'Configuración General';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.pages.general-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $claves = [
            'logo', 'favicon',
            'telefono_1', 'telefono_2',
            'whatsapp_1', 'whatsapp_2',
            'oficina_principal', 'correo_contacto',
            'facebook_url', 'instagram_url', 'tiktok_url',
        ];

        $this->form->fill(
            collect($claves)->mapWithKeys(fn ($c) => [$c => setting($c)])->toArray()
        );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identidad visual')->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->label('Logo del sitio')
                        ->image()
                        ->imagePreviewHeight('80')
                        ->disk('public')
                        ->directory('site')
                        ->helperText('Formatos: PNG, SVG, WEBP. Recomendado: fondo transparente.'),

                    Forms\Components\FileUpload::make('favicon')
                        ->label('Favicon')
                        ->image()
                        ->imagePreviewHeight('48')
                        ->disk('public')
                        ->directory('site')
                        ->helperText('Formatos: ICO, PNG. Tamaño recomendado: 32×32 px.'),
                ])->columns(2),

                Forms\Components\Section::make('Contacto')->schema([
                    Forms\Components\TextInput::make('telefono_1')
                        ->label('Teléfono 1')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->validationMessages([
                            'required' => 'El teléfono principal es obligatorio.',
                            'max'      => 'El teléfono no puede superar los 20 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('telefono_2')
                        ->label('Teléfono 2')
                        ->tel()
                        ->maxLength(20)
                        ->validationMessages([
                            'max' => 'El teléfono 2 no puede superar los 20 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('whatsapp_1')
                        ->label('WhatsApp 1 (con código de país)')
                        ->helperText('Ej: 527711910395')
                        ->required()
                        ->maxLength(20)
                        ->validationMessages([
                            'required' => 'El número de WhatsApp principal es obligatorio.',
                            'max'      => 'El número de WhatsApp no puede superar los 20 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('whatsapp_2')
                        ->label('WhatsApp 2 (con código de país)')
                        ->helperText('Ej: 527717818005')
                        ->maxLength(20)
                        ->validationMessages([
                            'max' => 'El número de WhatsApp 2 no puede superar los 20 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('correo_contacto')
                        ->label('Correo receptor del formulario de contacto')
                        ->email()
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'El correo de contacto es obligatorio.',
                            'email'    => 'El correo de contacto debe ser una dirección válida.',
                            'max'      => 'El correo no puede superar los 150 caracteres.',
                        ]),
                ])->columns(2),

                Forms\Components\Section::make('Oficina principal')->schema([
                    Forms\Components\Select::make('oficina_principal')
                        ->label('Sede / Oficina principal')
                        ->options(Cobertura::activos()->pluck('nombre', 'id'))
                        ->searchable()
                        ->placeholder('Selecciona una zona de cobertura')
                        ->helperText('Se usará para mostrar la dirección principal en el sitio.'),
                ]),

                Forms\Components\Section::make('Redes sociales')->schema([
                    Forms\Components\TextInput::make('facebook_url')
                        ->label('Facebook')
                        ->url()
                        ->prefix('🌐')
                        ->placeholder('https://facebook.com/tu-pagina')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('instagram_url')
                        ->label('Instagram')
                        ->url()
                        ->prefix('🌐')
                        ->placeholder('https://instagram.com/tu-usuario')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('tiktok_url')
                        ->label('TikTok')
                        ->url()
                        ->prefix('🌐')
                        ->placeholder('https://tiktok.com/@tu-usuario')
                        ->maxLength(255),
                ])->columns(3)
                  ->description('Deja en blanco las redes que no tengas. Solo se mostrarán en el sitio las que tengan URL.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $clave => $valor) {
            Configuracion::set($clave, $valor);
        }

        // Invalida todo el caché de configuración
        foreach (array_keys($state) as $clave) {
            Cache::forget("config_{$clave}");
        }

        Notification::make()
            ->title('Configuración general guardada correctamente.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
