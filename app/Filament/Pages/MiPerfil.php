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
            'banco'    => $user->banco,
            'clabe'    => $user->clabe,
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

                \Filament\Schemas\Components\Section::make('Datos bancarios')
                    ->description('Estos datos se usan para el pago de tus comisiones.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('banco')
                            ->label('Banco')
                            ->maxLength(100)
                            ->placeholder('Ej: BBVA, Banorte, HSBC…')
                            ->validationMessages([
                                'max' => 'El nombre del banco no puede superar los 100 caracteres.',
                            ]),

                        Forms\Components\TextInput::make('clabe')
                            ->label('CLABE interbancaria')
                            ->maxLength(18)
                            ->minLength(18)
                            ->placeholder('18 dígitos')
                            ->hint('Exactamente 18 dígitos')
                            ->extraInputAttributes(['style' => 'font-family:monospace;letter-spacing:2px;'])
                            ->validationMessages([
                                'min' => 'La CLABE debe tener exactamente 18 dígitos.',
                                'max' => 'La CLABE debe tener exactamente 18 dígitos.',
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
            'data.banco'    => ['nullable', 'string', 'max:100'],
            'data.clabe'    => ['nullable', 'digits:18'],
        ]);

        $user = Auth::user();

        $user->update([
            'name'     => $validated['name'],
            'telefono' => $validated['telefono'] ?? null,
            'banco'    => $validated['banco'] ?? null,
            'clabe'    => $validated['clabe'] ?? null,
        ]);

        $this->form->fill([
            'name'     => $user->fresh()->name,
            'email'    => $user->fresh()->email,
            'telefono' => $user->fresh()->telefono,
            'banco'    => $user->fresh()->banco,
            'clabe'    => $user->fresh()->clabe,
        ]);

        Notification::make()
            ->title('Perfil actualizado correctamente')
            ->success()
            ->send();
    }
}
