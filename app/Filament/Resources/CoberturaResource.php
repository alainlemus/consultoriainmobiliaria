<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoberturaResource\Pages;
use App\Models\Cobertura;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoberturaResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = Cobertura::class;
    protected static ?string $navigationIcon  = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Cobertura';
    protected static ?string $modelLabel      = 'Zona de cobertura';
    protected static ?string $pluralModelLabel = 'Cobertura';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Zona de cobertura')->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Estado / Nombre')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->rows(3),

                Forms\Components\TextInput::make('detalle')
                    ->label('Detalle (dirección o nota de contacto)')
                    ->required()
                    ->maxLength(255)
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
                Tables\Columns\TextColumn::make('orden')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(70)
                    ->wrap(),

                Tables\Columns\TextColumn::make('detalle')
                    ->label('Detalle')
                    ->limit(50),

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
            'index'  => Pages\ListCoberturas::route('/'),
            'create' => Pages\CreateCobertura::route('/create'),
            'edit'   => Pages\EditCobertura::route('/{record}/edit'),
        ];
    }
}
