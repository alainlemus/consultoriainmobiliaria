<?php

namespace App\Filament\Pages;

use App\Models\Configuracion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AvisoPrivacidadSettings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Aviso de Privacidad';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 12;
    protected static string  $view            = 'filament.pages.aviso-privacidad-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'aviso_privacidad' => Configuracion::get('aviso_privacidad', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Aviso de Privacidad')
                    ->description('Este contenido se mostrará en la página pública /aviso-de-privacidad.')
                    ->schema([
                        Forms\Components\RichEditor::make('aviso_privacidad')
                            ->label('Contenido del aviso')
                            ->required()
                            ->fileAttachmentsDirectory('aviso')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Configuracion::set('aviso_privacidad', $data['aviso_privacidad']);
        Cache::forget('config_aviso_privacidad');

        Notification::make()
            ->title('Aviso de privacidad guardado correctamente.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar')
                ->action('save'),
        ];
    }
}
