<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcesoResource\Pages;
use App\Models\Proceso;
use Filament\Forms;
use Filament\Forms\Form;
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
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Proceso';
    protected static ?string $modelLabel      = 'Paso del proceso';
    protected static ?string $pluralModelLabel = 'Proceso (pasos)';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Paso del proceso')->schema([
                Forms\Components\TextInput::make('numero')
                    ->label('Número (ej. 01, 02…)')
                    ->required()
                    ->maxLength(4),

                Forms\Components\TextInput::make('titulo')
                    ->label('Título del paso')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Visibilidad')->schema([
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
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
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
