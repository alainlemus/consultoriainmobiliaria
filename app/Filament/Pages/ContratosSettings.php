<?php

namespace App\Filament\Pages;

use App\Models\Configuracion;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ContratosSettings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Contratos para clientes';
    protected static ?string $title           = 'Contratos para clientes';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 90;
    protected string $view = 'filament.pages.contratos-settings';

    public ?array $data = [];

    protected static array $claves = [
        'firma_prestador',
        'firma_juridico',
        'contrato_intro',
        'contrato_declaraciones_prestador',
        'contrato_declaraciones_interesado',
        'contrato_clausulas',
        'convenio_intro',
        'convenio_clausulas',
    ];

    public function mount(): void
    {
        $this->form->fill(
            collect(static::$claves)
                ->mapWithKeys(fn ($c) => [$c => Configuracion::get($c, '')])
                ->toArray()
        );
    }

    public function form(Schema $schema): Schema
    {
        $hint = fn (string $text) => $text;

        $placeholders = '**Placeholders disponibles:** `{ciudad}` `{fecha}` `{domicilio}` `{acreditado}` `{dom_acreditado}` `{tipo_tramite}` `{curp}` `{rfc}` `{nss}` `{folio}` `{monto_credito}` `{pct_honorarios}` `{monto_honorarios}`';

        return $schema
            ->schema([
                // ── FIRMAS ────────────────────────────────────────────────────
                \Filament\Schemas\Components\Section::make('Firmas')
                    ->description('Nombres que aparecen en el bloque de firmas de ambos documentos.')
                    ->icon('heroicon-o-pencil-square')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('firma_prestador')
                            ->label('Firma de "El Prestador"')
                            ->required()
                            ->maxLength(150)
                            ->validationMessages([
                                'required' => 'El nombre del prestador es obligatorio.',
                                'max'      => 'El nombre no puede superar los 150 caracteres.',
                            ]),

                        Forms\Components\TextInput::make('firma_juridico')
                            ->label('Firma por parte del Jurídico')
                            ->required()
                            ->maxLength(150)
                            ->validationMessages([
                                'required' => 'El nombre del jurídico es obligatorio.',
                                'max'      => 'El nombre no puede superar los 150 caracteres.',
                            ]),
                    ]),

                // ── CONTRATO DE PRESTACIÓN DE SERVICIOS ───────────────────────
                \Filament\Schemas\Components\Section::make('Contrato de Prestación de Servicios')
                    ->icon('heroicon-o-document-text')
                    ->description($placeholders)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('contrato_intro')
                            ->label('Párrafo introductorio')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('contrato_declaraciones_prestador')
                            ->label('Declaraciones de "El Prestador"')
                            ->helperText('Una declaración por línea.')
                            ->rows(8)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('contrato_declaraciones_interesado')
                            ->label('Declaraciones del Interesado')
                            ->helperText('Una declaración por línea.')
                            ->rows(8)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('contrato_clausulas')
                            ->label('Cláusulas')
                            ->helperText('Una cláusula por línea. Ej: A.-) texto...')
                            ->rows(16)
                            ->columnSpanFull(),
                    ]),

                // ── CONVENIO DE HONORARIOS ────────────────────────────────────
                \Filament\Schemas\Components\Section::make('Convenio de Honorarios')
                    ->icon('heroicon-o-lock-closed')
                    ->description($placeholders)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('convenio_intro')
                            ->label('Párrafo introductorio')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('convenio_clausulas')
                            ->label('Cláusulas (Primera, Segunda, …)')
                            ->helperText('Separa cada cláusula con una línea en blanco.')
                            ->rows(20)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $clave => $valor) {
            Configuracion::set($clave, $valor ?? '');
        }

        Notification::make()
            ->title('Configuración de contratos guardada.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
