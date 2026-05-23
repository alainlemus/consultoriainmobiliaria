<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcesoResource\Pages;
use App\Models\Proceso;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProcesoResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = Proceso::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Proceso';
    protected static ?string $modelLabel      = 'Paso del proceso';
    protected static ?string $pluralModelLabel = 'Proceso (pasos)';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Paso del proceso')->schema([
                Forms\Components\TextInput::make('numero')
                    ->label('Número (ej. 01, 02…)')
                    ->required()
                    ->maxLength(4)
                    ->validationMessages([
                        'required' => 'El número del paso es obligatorio.',
                        'max'      => 'El número no puede superar los 4 caracteres.',
                    ]),

                Forms\Components\TextInput::make('titulo')
                    ->label('Título del paso')
                    ->required()
                    ->maxLength(100)
                    ->validationMessages([
                        'required' => 'El título del paso es obligatorio.',
                        'max'      => 'El título no puede superar los 100 caracteres.',
                    ]),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull()
                    ->validationMessages([
                        'required' => 'La descripción del paso es obligatoria.',
                    ]),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Visibilidad')->schema([
                Forms\Components\Toggle::make('activo')
                    ->label('Visible en el sitio')
                    ->default(true),

                Forms\Components\TextInput::make('orden')
                    ->label('Orden de aparición')
                    ->numeric()
                    ->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('orden')
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->sortable()
                    ->width(60),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(70)
                    ->wrap(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label('Editar'),
                \Filament\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('orden');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProcesos::route('/'),
            'create' => Pages\CreateProceso::route('/create'),
            'edit'   => Pages\EditProceso::route('/{record}/edit'),
        ];
    }
}
