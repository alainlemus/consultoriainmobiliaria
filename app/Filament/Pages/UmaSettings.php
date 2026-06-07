<?php

namespace App\Filament\Pages;

use App\Models\Configuracion;
use App\Services\UmaService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

/**
 * Página de configuración de la UMA (Unidad de Medida y Actualización).
 *
 * Permite:
 *  - Ver el valor actual almacenado en BD
 *  - Editar manualmente si se conoce el valor oficial
 *  - Actualizar automáticamente desde la API del INEGI
 */
class UmaSettings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-calculator';
    protected static ?string $navigationLabel                 = 'UMA';
    protected static ?string $title                           = 'Valor de la UMA';
    protected static string|\UnitEnum|null $navigationGroup   = 'Configuración del sitio';
    protected static ?int    $navigationSort                   = 45;
    protected string $view = 'filament.pages.uma-settings';

    public ?array $data = [];

    // ─────────────────────────────────────────────────────────────────────
    // LIFECYCLE
    // ─────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill([
            'uma_diaria'   => UmaService::getUmaDiaria(),
            'uma_mensual'  => UmaService::getUmaMensual(),
            'uma_anual'    => UmaService::getUmaAnual(),
            'uma_vigencia' => UmaService::getVigencia(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Valor de la UMA vigente')
                    ->description('La UMA se actualiza cada 1 de febrero según la resolución del INEGI publicada en el DOF.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(4)->schema([
                            Forms\Components\TextInput::make('uma_vigencia')
                                ->label('Año de vigencia')
                                ->placeholder('2026')
                                ->numeric()
                                ->minValue(2016)
                                ->maxValue(2099)
                                ->required(),

                            Forms\Components\TextInput::make('uma_diaria')
                                ->label('UMA Diaria ($)')
                                ->placeholder('117.63')
                                ->numeric()
                                ->step(0.01)
                                ->minValue(50)
                                ->maxValue(1000)
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $diaria = (float) $state;
                                    if ($diaria > 0) {
                                        $set('uma_mensual', round($diaria * 30.4, 2));
                                        $set('uma_anual',   round($diaria * 365,  2));
                                    }
                                })
                                ->required(),

                            Forms\Components\TextInput::make('uma_mensual')
                                ->label('UMA Mensual ($)')
                                ->placeholder('3,575.95')
                                ->numeric()
                                ->step(0.01)
                                ->prefix('$')
                                ->readOnly()
                                ->helperText('Calculado: diaria × 30.4'),

                            Forms\Components\TextInput::make('uma_anual')
                                ->label('UMA Anual ($)')
                                ->placeholder('42,934.95')
                                ->numeric()
                                ->step(0.01)
                                ->prefix('$')
                                ->readOnly()
                                ->helperText('Calculado: diaria × 365'),
                        ]),
                    ]),

                Section::make('Configuración API INEGI')
                    ->description('Token necesario para actualizar automáticamente desde la API oficial del INEGI.')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('inegi_token_estado')
                            ->label('Estado de auto-actualización')
                            ->content(function (): string {
                                $token = config('services.inegi.token');
                                if ($token) {
                                    return '✅ Token configurado — Nota: la API pública del INEGI solo expone '
                                        . 'indicadores BISE. El indicador UMA vive en BIE (no accesible vía API '
                                        . 'pública). El botón "Actualizar desde INEGI" intentará scraping como '
                                        . 'respaldo, pero puede no encontrar el valor. '
                                        . 'Se recomienda actualizar manualmente cada febrero con el valor del DOF.';
                                }
                                return '⚠️ Sin token configurado (INEGI_TOKEN en .env). '
                                    . 'De cualquier forma, la auto-actualización no está disponible '
                                    . 'porque el indicador UMA está en BIE (no expuesto en la API pública). '
                                    . 'Actualizar el valor manualmente cada febrero.';
                            }),
                    ]),
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ACTIONS
    // ─────────────────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('actualizar_inegi')
                ->label('Actualizar desde INEGI')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Actualizar UMA desde INEGI')
                ->modalDescription('Se consultará la API oficial del INEGI o su sitio web para obtener el valor vigente. ¿Continuar?')
                ->modalSubmitActionLabel('Sí, actualizar')
                ->action(function () {
                    try {
                        $resultado = UmaService::actualizar();

                        if ($resultado['datos']) {
                            // Refrescar el formulario con los valores nuevos
                            $this->form->fill([
                                'uma_diaria'   => UmaService::getUmaDiaria(),
                                'uma_mensual'  => UmaService::getUmaMensual(),
                                'uma_anual'    => UmaService::getUmaAnual(),
                                'uma_vigencia' => UmaService::getVigencia(),
                            ]);

                            Notification::make()
                                ->title('UMA actualizada')
                                ->body("Fuente: {$resultado['fuente']} · Valor diario: $" . number_format(UmaService::getUmaDiaria(), 2))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No se pudo actualizar desde INEGI')
                                ->body('Verifica tu INEGI_TOKEN en .env: debe ser de la API de INDICADORES BIE, '
                                    . 'NO del DENUE (token distinto). Si no tienes token BIE, el botón usará '
                                    . 'scraping pero puede no encontrar el valor. '
                                    . 'Puedes actualizar el valor manualmente con el formulario.')
                                ->warning()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Log::error('[UmaSettings] Error al actualizar UMA: ' . $e->getMessage());
                        Notification::make()
                            ->title('Error al actualizar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // SAVE
    // ─────────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $data = $this->form->getState();

        $diaria   = (float) $data['uma_diaria'];
        $mensual  = round($diaria * 30.4, 2);
        $anual    = round($diaria * 365,  2);
        $vigencia = (int) $data['uma_vigencia'];

        Configuracion::set(UmaService::CLAVE_DIARIA,   $diaria);
        Configuracion::set(UmaService::CLAVE_MENSUAL,   $mensual);
        Configuracion::set(UmaService::CLAVE_ANUAL,     $anual);
        Configuracion::set(UmaService::CLAVE_VIGENCIA,  $vigencia);

        // Refrescar con los valores calculados
        $this->form->fill([
            'uma_diaria'   => $diaria,
            'uma_mensual'  => $mensual,
            'uma_anual'    => $anual,
            'uma_vigencia' => $vigencia,
        ]);

        Notification::make()
            ->title('UMA guardada correctamente')
            ->body("Diaria: \${$diaria} · Mensual: \$" . number_format($mensual, 2) . " · Vigencia: {$vigencia}")
            ->success()
            ->send();
    }
}
