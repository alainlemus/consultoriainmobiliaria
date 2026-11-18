<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoTipoResource\Pages;
use App\Models\VehiculoTipo;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/** Tipos de vehículo (Moto, Coche…): qué fotos y checklist se piden en cada salida y entrega. */
class VehiculoTipoResource extends Resource
{
    protected static ?string $model = VehiculoTipo::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Tipos de vehículo';

    protected static string|\UnitEnum|null $navigationGroup = 'Vehículos';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Tipo de vehículo';

    protected static ?string $pluralModelLabel = 'Tipos de vehículo';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function canDelete($record): bool
    {
        return $record->vehiculos()->doesntExist();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Tipo')->schema([
                Forms\Components\TextInput::make('nombre')->label('Nombre')->required()->maxLength(60)->placeholder('Moto, Coche, Camioneta…'),
                Forms\Components\TextInput::make('licencia_tipo')->label('Licencia que se pide')->maxLength(60)
                    ->helperText('Solo informativo: si el asesor no tiene licencia vigente puede usar el vehículo y se muestra un aviso.'),
                Forms\Components\TextInput::make('servicio_cada_km')->label('Servicio cada (km)')->numeric()->minValue(0),
                Forms\Components\Toggle::make('activo')->label('Activo')->default(true)->inline(false),
            ])->columns(2),

            Section::make('Fotos obligatorias')
                ->description('Además de la foto del tablero (km y gasolina), que siempre se pide. Se toman en la salida y en la entrega para poder compararlas.')
                ->schema([
                    Forms\Components\Repeater::make('fotos_requeridas')->hiddenLabel()
                        ->schema([
                            Forms\Components\TextInput::make('etiqueta')->label('Foto')->required()->maxLength(40)->placeholder('Frente'),
                            Forms\Components\TextInput::make('clave')->label('Clave interna')->required()->maxLength(30)
                                ->alphaDash()->helperText('Sin espacios, ej. frente. No la cambies si ya hay salidas registradas.'),
                        ])->columns(2)
                        ->addActionLabel('Agregar foto')->reorderable()->defaultItems(0),
                ]),

            Section::make('Checklist')
                ->description('Lo que el asesor confirma al salir y al entregar.')
                ->schema([
                    Forms\Components\Repeater::make('checklist')->hiddenLabel()
                        ->simple(Forms\Components\TextInput::make('item')->label('Punto')->required()->maxLength(100))
                        ->addActionLabel('Agregar punto')->reorderable()->defaultItems(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Tipo')->weight('bold')->searchable(),
                Tables\Columns\TextColumn::make('vehiculos_count')->label('Vehículos')->counts('vehiculos'),
                Tables\Columns\TextColumn::make('fotos_requeridas')->label('Fotos')
                    ->getStateUsing(fn (VehiculoTipo $r) => collect($r->fotosObligatorias())->pluck('etiqueta')->join(', '))->wrap(),
                Tables\Columns\TextColumn::make('licencia_tipo')->label('Licencia')->placeholder('—'),
                Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehiculoTipos::route('/'),
            'create' => Pages\CreateVehiculoTipo::route('/create'),
            'edit' => Pages\EditVehiculoTipo::route('/{record}/edit'),
        ];
    }
}
