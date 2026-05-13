<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EtapaTramiteResource\Pages;
use App\Models\EtapaTramite;
use App\Models\TipoTramite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EtapaTramiteResource extends Resource
{
    protected static ?string $model = EtapaTramite::class;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Etapas del Pipeline';
    protected static ?string $modelLabel = 'Etapa';
    protected static ?string $pluralModelLabel = 'Etapas del Pipeline';
    protected static ?string $navigationGroup = 'Configuración CRM';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('tipo_tramite_id')
                ->label('Tipo de Trámite')
                ->options(TipoTramite::pluck('nombre', 'id'))
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre de la Etapa')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\Select::make('color')
                ->label('Color del badge')
                ->options([
                    'gray'    => 'Gris',
                    'blue'    => 'Azul',
                    'yellow'  => 'Amarillo',
                    'orange'  => 'Naranja',
                    'green'   => 'Verde',
                    'red'     => 'Rojo',
                    'purple'  => 'Morado',
                ])
                ->default('gray')
                ->required(),
            Forms\Components\TextInput::make('orden')
                ->label('Orden')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('es_final')
                ->label('Es etapa de cierre')
                ->helperText('Marcar si esta etapa equivale a "cerrado/completado"'),
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
                Tables\Columns\TextColumn::make('tipoTramite.nombre')
                    ->label('Tipo de Trámite')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Etapa')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('color')
                    ->label('Color')
                    ->colors([
                        'secondary' => 'gray',
                        'primary'   => 'blue',
                        'warning'   => fn ($state) => in_array($state, ['yellow', 'orange']),
                        'success'   => 'green',
                        'danger'    => 'red',
                    ]),
                Tables\Columns\IconColumn::make('es_final')
                    ->label('Cierre')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('expedientes_count')
                    ->label('Expedientes')
                    ->counts('expedientes')
                    ->alignCenter(),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->groups(['tipoTramite.nombre'])
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEtapaTramites::route('/'),
            'create' => Pages\CreateEtapaTramite::route('/create'),
            'edit'   => Pages\EditEtapaTramite::route('/{record}/edit'),
        ];
    }
}
