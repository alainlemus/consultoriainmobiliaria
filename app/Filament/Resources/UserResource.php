<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
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
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
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

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Foto de perfil')
                ->description('Subida por el asesor desde la app móvil.')
                ->schema([
                    Forms\Components\ViewField::make('foto_perfil_url')
                        ->label('')
                        ->view('filament.forms.components.foto-perfil')
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->visibleOn(['view', 'edit']),

            \Filament\Schemas\Components\Section::make('Información personal')
                ->description('Datos de acceso del usuario al sistema de administración.')
                ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(100)
                    ->hint('Nombre real del asesor o administrador')
                    ->validationMessages([
                        'required' => 'El nombre completo es obligatorio.',
                        'max'      => 'El nombre no puede superar los 100 caracteres.',
                    ]),

                Forms\Components\TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(150)
                    ->hint('Se usa para iniciar sesión — debe ser único')
                    ->validationMessages([
                        'required' => 'El correo electrónico es obligatorio.',
                        'email'    => 'Ingresa un correo electrónico válido (ej: usuario@dominio.com).',
                        'unique'   => 'Este correo ya está registrado en el sistema.',
                        'max'      => 'El correo no puede superar los 150 caracteres.',
                    ]),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Contraseña')
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
                        : null)
                    ->validationMessages([
                        'required' => 'La contraseña es obligatoria al crear un usuario.',
                        'min'      => 'La contraseña debe tener al menos 8 caracteres.',
                    ]),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->same('password')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(false)
                    ->validationMessages([
                        'required' => 'Debes confirmar la contraseña.',
                        'same'     => 'La confirmación no coincide con la contraseña ingresada.',
                    ]),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Datos de contacto')
                ->schema([
                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('55 1234 5678')
                        ->columnSpanFull()
                        ->validationMessages([
                            'max' => 'El teléfono no puede superar los 20 caracteres.',
                        ]),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Rol en el sistema')
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
                Tables\Columns\ImageColumn::make('foto_perfil_url')
                    ->label('Foto')
                    ->getStateUsing(fn (User $record) => $record->foto_perfil_url)
                    ->circular()
                    ->defaultImageUrl(null)
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('primary')
                    ->separator(', ')
                    ->tooltip('Rol que determina los permisos del usuario en el sistema'),

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
                \Filament\Actions\EditAction::make()->label('Editar'),
                \Filament\Actions\Action::make('toggleActivo')
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
                \Filament\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->hidden(fn ($record) => $record->id === Auth::id()),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
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
