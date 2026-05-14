<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Rol'   => $record->roles->pluck('name')->join(', ') ?: '—',
            'Estado'=> $record->activo ? 'Activo' : 'Inactivo',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    // Mismo grupo que Shield (lee la traducción publicada)
    public static function getNavigationGroup(): ?string
    {
        return __('filament-shield::filament-shield.nav.group');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información personal')
                ->description('Datos de acceso del usuario al sistema de administración.')
                ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(100)
                    ->hint('Nombre real del asesor o administrador'),

                Forms\Components\TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(150)
                    ->hint('Se usa para iniciar sesión — debe ser único'),
            ])->columns(2),

            Forms\Components\Section::make('Contraseña')
                ->description('Mínimo 8 caracteres. En modo edición, deja en blanco para no cambiar la contraseña actual.')
                ->schema([
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::default())
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? 'Deja en blanco para mantener la contraseña actual.'
                        : null),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->same('password')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(false),
            ])->columns(2),

            Forms\Components\Section::make('Datos bancarios para comisiones')
                ->description('CLABE y banco al que se transferirán las comisiones del asesor. Solo visible para el administrador.')
                ->schema([
                    Forms\Components\TextInput::make('banco')
                        ->label('Banco')
                        ->maxLength(100)
                        ->placeholder('Ej: BBVA, Banamex, Banorte…'),

                    Forms\Components\TextInput::make('clabe')
                        ->label('CLABE interbancaria')
                        ->maxLength(18)
                        ->minLength(18)
                        ->regex('/^\d{18}$/')
                        ->validationMessages(['regex' => 'La CLABE debe tener exactamente 18 dígitos numéricos.'])
                        ->placeholder('18 dígitos')
                        ->hint('18 dígitos — requerida para transferencia'),
                ])->columns(2),

            Forms\Components\Section::make('Rol en el sistema')
                ->description('Define qué puede ver y hacer este usuario. "super_admin" tiene acceso total. "asesor" solo ve sus propios expedientes, prospectos y comisiones.')
                ->schema([
                Forms\Components\Select::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->options(Role::all()->pluck('name', 'id'))
                    ->preload()
                    ->searchable()
                    ->multiple()
                    ->required()
                    ->validationMessages(['required' => 'Debes asignar al menos un rol al usuario.'])
                    ->helperText('super_admin: acceso total | asesor: acceso restringido a sus registros'),

                Forms\Components\Toggle::make('activo')
                    ->label('Usuario activo')
                    ->default(true)
                    ->helperText('Si se desactiva, el usuario no podrá ingresar al sistema.')
                    ->disabled(fn ($record) => $record?->id === Auth::id()),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('primary')
                    ->separator(', ')
                    ->tooltip('Rol que determina los permisos del usuario en el sistema'),

                Tables\Columns\TextColumn::make('banco')
                    ->label('Banco')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('clabe')
                    ->label('CLABE')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('CLABE copiada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip('Usuarios inactivos no pueden iniciar sesión'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Filtrar por rol')
                    ->relationship('roles', 'name')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\Action::make('toggleActivo')
                    ->label(fn ($record) => $record->activo ? 'Pausar' : 'Activar')
                    ->icon(fn ($record) => $record->activo ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn ($record) => $record->activo ? 'warning' : 'success')
                    ->hidden(fn ($record) => $record->id === Auth::id())
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->activo ? 'Pausar usuario' : 'Activar usuario')
                    ->modalDescription(fn ($record) => $record->activo
                        ? '¿Confirmas que deseas pausar a este usuario? No podrá ingresar al sistema.'
                        : '¿Confirmas que deseas activar a este usuario?')
                    ->action(fn ($record) => $record->update(['activo' => ! $record->activo])),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->hidden(fn ($record) => $record->id === Auth::id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->action(function ($records) {
                            $records->except(Auth::id())->each->delete();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
