<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoTramiteResource\Pages;
use App\Models\TipoTramite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TipoTramiteResource extends Resource
{
    protected static ?string $model = TipoTramite::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Tipos de Trámite';
    protected static ?string $modelLabel = 'Tipo de Trámite';
    protected static ?string $pluralModelLabel = 'Tipos de Trámite';
    protected static ?string $navigationGroup = 'Configuración CRM';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información General')
                ->description('Define los tipos de trámite que ofrece la consultoría. Cada tipo tiene su propio flujo de etapas y documentos requeridos.')
                ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                        $set('slug', Str::slug($state))
                    )
                    ->hint('Ej: FOVISSSTE, INFONAVIT, Avalúo, Escrituras'),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->hint('Se genera automáticamente — no modificar'),
                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull()
                    ->hint('Descripción interna para el equipo'),
            ])->columns(2),

            Forms\Components\Section::make('Configuración')
                ->description('El porcentaje de honorarios es el valor por defecto que se aplica al crear un expediente de este tipo. Puede ajustarse por expediente.')
                ->schema([
                Forms\Components\TextInput::make('porcentaje_honorarios')
                    ->label('% Honorarios (por defecto)')
                    ->numeric()
                    ->suffix('%')
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->hint('Se usa como valor inicial en nuevos expedientes'),
                Forms\Components\TextInput::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->hint('Número de aparición en listas y selectores'),
                Forms\Components\Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->hint('Los inactivos no aparecen al crear expedientes'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('orden')
                    ->label('#')
                    ->sortable()
                    ->width(60),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('porcentaje_honorarios')
                    ->label('% Honorarios')
                    ->suffix('%')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('etapas_count')
                    ->label('Etapas')
                    ->counts('etapas')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('expedientes_count')
                    ->label('Expedientes')
                    ->counts('expedientes')
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTipoTramites::route('/'),
            'create' => Pages\CreateTipoTramite::route('/create'),
            'edit'   => Pages\EditTipoTramite::route('/{record}/edit'),
        ];
    }
}
