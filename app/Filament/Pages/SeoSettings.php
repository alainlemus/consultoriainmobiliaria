<?php

namespace App\Filament\Pages;

use App\Models\Configuracion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class SeoSettings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?string $navigationIcon  = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'SEO';
    protected static ?string $title           = 'SEO';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 11;
    protected static string  $view            = 'filament.pages.seo-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $claves = [
            'seo_titulo', 'seo_descripcion', 'seo_keywords',
            'seo_og_imagen', 'seo_autor', 'seo_robots',
            'ga4_id', 'gsc_verification',
        ];

        $this->form->fill(
            collect($claves)->mapWithKeys(fn ($c) => [$c => setting($c)])->toArray()
        );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Etiquetas meta básicas')->schema([
                    Forms\Components\TextInput::make('seo_titulo')
                        ->label('Título de la página (meta title)')
                        ->required()
                        ->maxLength(70)
                        ->helperText('Recomendado: máximo 60-70 caracteres.')
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'El título SEO es obligatorio.',
                            'max'      => 'El título no puede superar los 70 caracteres.',
                        ]),

                    Forms\Components\Textarea::make('seo_descripcion')
                        ->label('Descripción (meta description)')
                        ->required()
                        ->rows(3)
                        ->maxLength(165)
                        ->helperText('Recomendado: máximo 150-160 caracteres.')
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'La descripción SEO es obligatoria.',
                            'max'      => 'La descripción no puede superar los 165 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('seo_keywords')
                        ->label('Palabras clave (meta keywords)')
                        ->helperText('Separadas por comas. Ej: avalúos, INFONAVIT, Hidalgo')
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->validationMessages([
                            'max' => 'Las palabras clave no pueden superar los 255 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('seo_autor')
                        ->label('Autor (meta author)')
                        ->maxLength(100)
                        ->validationMessages([
                            'max' => 'El nombre del autor no puede superar los 100 caracteres.',
                        ]),

                    Forms\Components\Select::make('seo_robots')
                        ->label('Robots (meta robots)')
                        ->options([
                            'index, follow'     => 'index, follow (recomendado)',
                            'noindex, follow'   => 'noindex, follow',
                            'index, nofollow'   => 'index, nofollow',
                            'noindex, nofollow' => 'noindex, nofollow',
                        ])
                        ->default('index, follow')
                        ->required()
                        ->validationMessages([
                            'required' => 'Debes seleccionar el valor de robots.',
                        ]),
                ])->columns(2),

                Forms\Components\Section::make('Open Graph / Redes sociales')->schema([
                    Forms\Components\FileUpload::make('seo_og_imagen')
                        ->label('Imagen OG (Open Graph)')
                        ->image()
                        ->imagePreviewHeight('120')
                        ->disk('public')
                        ->directory('site')
                        ->helperText('Se muestra al compartir en redes sociales. Recomendado: 1200×630 px.')
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Analítica y verificación')->schema([
                    Forms\Components\TextInput::make('ga4_id')
                        ->label('Google Analytics 4 — Measurement ID')
                        ->placeholder('G-XXXXXXXXXX')
                        ->helperText('ID de medición de tu propiedad GA4. Ej: G-QYJX1JS69D')
                        ->maxLength(30),

                    Forms\Components\TextInput::make('gsc_verification')
                        ->label('Google Search Console — código de verificación')
                        ->placeholder('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                        ->helperText('Valor del meta tag google-site-verification.')
                        ->maxLength(100),
                ])->columns(1),
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
            ->title('Configuración SEO guardada correctamente.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
