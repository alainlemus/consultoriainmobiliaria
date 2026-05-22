<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoTramiteResource\Pages;
use App\Models\TipoTramite;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TipoTramiteResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = TipoTramite::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Tipos de Trámite';
    protected static ?string $modelLabel = 'Tipo de Trámite';
    protected static ?string $pluralModelLabel = 'Tipos de Trámite';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración CRM';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Información General')
                ->description('Define los tipos de trámite que ofrece la consultoría. Cada tipo tiene su propio flujo de etapas y documentos requeridos.')
                ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'El nombre del tipo de trámite es obligatorio.',
                        'max'      => 'El nombre no puede superar los 255 caracteres.',
                        'unique'   => 'Ya existe un tipo de trámite con ese nombre.',
                    ])
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
                    ->hint('Se genera automáticamente — no modificar')
                    ->validationMessages([
                        'required' => 'El slug es obligatorio.',
                        'unique'   => 'Ya existe un tipo de trámite con ese slug.',
                        'max'      => 'El slug no puede superar los 255 caracteres.',
                    ]),
                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull()
                    ->hint('Descripción interna para el equipo'),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Configuración')
                ->description('El porcentaje de honorarios es el valor por defecto que se aplica al crear un expediente de este tipo. Puede ajustarse por expediente.')
                ->schema([
                Forms\Components\TextInput::make('porcentaje_honorarios')
                    ->label('% Honorarios (por defecto)')
                    ->numeric()
                    ->suffix('%')
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->hint('Se usa como valor inicial en nuevos expedientes')
                    ->validationMessages([
                        'min' => 'El porcentaje no puede ser negativo.',
                        'max' => 'El porcentaje no puede superar el 100%.',
                    ]),
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
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
