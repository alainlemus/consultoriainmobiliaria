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
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


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
            Forms\Components\Section::make('Etapa del Pipeline')
                ->description('Las etapas definen el flujo de trabajo de cada tipo de trámite. Se muestran en orden en el expediente y en la gráfica del funnel del Dashboard.')
                ->columns(2)
                ->schema([
            Forms\Components\Select::make('tipo_tramite_id')
                ->label('Tipo de Trámite')
                ->options(TipoTramite::pluck('nombre', 'id'))
                ->required()
                ->searchable()
                ->hint('A qué tipo de trámite pertenece esta etapa')
                ->validationMessages([
                    'required' => 'Debes seleccionar el tipo de trámite.',
                ]),
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre de la Etapa')
                ->required()
                ->maxLength(255)
                ->hint('Ej: Precalificación, Integración de expediente, Firma de escrituras')
                ->validationMessages([
                    'required' => 'El nombre de la etapa es obligatorio.',
                    'max'      => 'El nombre no puede superar los 255 caracteres.',
                ]),
            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(2)
                ->columnSpanFull()
                ->hint('Describe qué actividades se realizan en esta etapa'),
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
                ->required()
                ->hint('Color visual del badge en la tabla de expedientes')
                ->validationMessages([
                    'required' => 'Debes seleccionar un color para la etapa.',
                ]),
            Forms\Components\TextInput::make('orden')
                ->label('Orden')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->validationMessages(['min' => 'El orden no puede ser negativo.'])
                ->hint('Número de secuencia — 1 es la primera etapa'),
            Forms\Components\Toggle::make('es_final')
                ->label('Es etapa de cierre')
                ->helperText('Activa si esta etapa representa la conclusión del trámite'),
            ])->columnSpanFull(),
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
