<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MiPerfil extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Mi Perfil';
    protected static ?string $title           = 'Mi Perfil';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 99;
    protected static ?string $slug            = 'mi-perfil';

    protected string $view = 'filament.pages.mi-perfil';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name'     => $user->name,
            'email'    => $user->email,
            'telefono' => $user->telefono,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Datos personales')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->validationMessages([
                                'required' => 'El nombre completo es obligatorio.',
                                'max'      => 'El nombre no puede superar los 255 caracteres.',
                            ]),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->disabled()
                            ->dehydrated(false)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('55 1234 5678')
                            ->validationMessages([
                                'max' => 'El teléfono no puede superar los 20 caracteres.',
                            ]),
                    ]),


            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $validated = $this->form->getState();

        $this->validate([
            'data.name'     => ['required', 'string', 'max:255'],
            'data.telefono' => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::user();

        $user->update([
            'name'     => $validated['name'],
            'telefono' => $validated['telefono'] ?? null,
        ]);

        $this->form->fill([
            'name'     => $user->fresh()->name,
            'email'    => $user->fresh()->email,
            'telefono' => $user->fresh()->telefono,
        ]);

        Notification::make()
            ->title('Perfil actualizado correctamente')
            ->success()
            ->send();
    }
}
