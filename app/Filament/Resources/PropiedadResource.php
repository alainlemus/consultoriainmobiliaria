<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropiedadResource\Pages;
use App\Models\Propiedad;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PropiedadResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?string $model = Propiedad::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-home-modern';
    protected static ?string $navigationLabel = 'Propiedades';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 6;
    protected static ?string $modelLabel      = 'Propiedad';
    protected static ?string $pluralModelLabel = 'Propiedades';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            \Filament\Schemas\Components\Section::make('Información general')->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Set $set) =>
                        $set('slug', Str::slug($state))
                    )
                    ->validationMessages([
                        'required' => 'El título de la propiedad es obligatorio.',
                        'max'      => 'El título no puede superar los 255 caracteres.',
                    ]),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->disabled()
                    ->dehydrated()
                    ->unique(ignoreRecord: true)
                    ->helperText('Se genera automáticamente.'),

                Forms\Components\Select::make('tipo')
                    ->label('Tipo de propiedad')
                    ->options(Propiedad::tipos())
                    ->required()
                    ->validationMessages([
                        'required' => 'Debes seleccionar el tipo de propiedad.',
                    ]),

                Forms\Components\Select::make('estatus')
                    ->label('Estatus')
                    ->options(Propiedad::estatuses())
                    ->default('en_venta')
                    ->required()
                    ->validationMessages([
                        'required' => 'Debes seleccionar el estatus de la propiedad.',
                    ]),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(4)
                    ->columnSpanFull(),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Ubicación')->schema([
                Forms\Components\TextInput::make('estado')
                    ->label('Estado')
                    ->required()
                    ->datalist([
                        'Aguascalientes','Baja California','Baja California Sur','Campeche',
                        'Chiapas','Chihuahua','Ciudad de México','Coahuila','Colima',
                        'Durango','Estado de México','Guanajuato','Guerrero','Hidalgo',
                        'Jalisco','Michoacán','Morelos','Nayarit','Nuevo León','Oaxaca',
                        'Puebla','Querétaro','Quintana Roo','San Luis Potosí','Sinaloa',
                        'Sonora','Tabasco','Tamaulipas','Tlaxcala','Veracruz','Yucatán','Zacatecas',
                    ])
                    ->validationMessages([
                        'required' => 'El estado de la propiedad es obligatorio.',
                    ]),

                Forms\Components\TextInput::make('municipio')
                    ->label('Municipio')
                    ->required()
                    ->validationMessages([
                        'required' => 'El municipio de la propiedad es obligatorio.',
                    ]),

                Forms\Components\TextInput::make('colonia')
                    ->label('Colonia'),

                Forms\Components\TextInput::make('direccion')
                    ->label('Dirección (referencia)')
                    ->columnSpanFull(),

                Forms\Components\Fieldset::make('Mapa (opcional)')
                    ->schema([
                        Forms\Components\TextInput::make('latitud')
                            ->label('Latitud')
                            ->numeric()
                            ->placeholder('Ej: 20.1234567')
                            ->helperText('Clic derecho en Google Maps → "¿Qué hay aquí?"'),

                        Forms\Components\TextInput::make('longitud')
                            ->label('Longitud')
                            ->numeric()
                            ->placeholder('Ej: -98.7654321'),

                        Forms\Components\Textarea::make('mapa_iframe')
                            ->label('Iframe de Google Maps')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('<iframe src="https://www.google.com/maps/embed?pb=..." ...></iframe>')
                            ->helperText('En Google Maps: Compartir → Insertar mapa → Copiar HTML. Si se llena este campo, tiene prioridad sobre las coordenadas.'),
                    ])->columns(2)->columnSpanFull(),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Características')->schema([
                Forms\Components\TextInput::make('precio')
                    ->label('Precio ($)')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('Dejar vacío para "Consultar precio"'),

                Forms\Components\TextInput::make('metros_construccion')
                    ->label('m² construcción')
                    ->numeric()
                    ->suffix('m²'),

                Forms\Components\TextInput::make('metros_terreno')
                    ->label('m² terreno')
                    ->numeric()
                    ->suffix('m²'),

                Forms\Components\TextInput::make('recamaras')
                    ->label('Recámaras')
                    ->numeric()
                    ->minValue(0),

                Forms\Components\TextInput::make('banos')
                    ->label('Baños')
                    ->numeric()
                    ->minValue(0),

                Forms\Components\CheckboxList::make('creditos')
                    ->label('Acepta créditos')
                    ->options([
                        'acepta_infonavit' => 'INFONAVIT',
                        'acepta_fovissste' => 'FOVISSSTE',
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($state, $record, Set $set) {
                        if (!$record) return;
                        $val = [];
                        if ($record->acepta_infonavit) $val[] = 'acepta_infonavit';
                        if ($record->acepta_fovissste) $val[] = 'acepta_fovissste';
                        $set('creditos', $val);
                    })
                    ->dehydrated(false)
                    ->reactive(),
            ])->columns(3),

            \Filament\Schemas\Components\Section::make('Imágenes')->schema([
                Forms\Components\FileUpload::make('imagenes')
                    ->label('Galería de imágenes')
                    ->multiple()
                    ->image()
                    ->reorderable()
                    ->directory('propiedades')
                    ->maxFiles(15)
                    ->columnSpanFull(),
            ]),

            \Filament\Schemas\Components\Section::make('Opciones')->schema([
                Forms\Components\Toggle::make('destacada')
                    ->label('Destacar en la página de inicio')
                    ->helperText('Las propiedades destacadas aparecen en la sección de la landing.'),

                Forms\Components\Toggle::make('acepta_infonavit')
                    ->label('Acepta INFONAVIT'),

                Forms\Components\Toggle::make('acepta_fovissste')
                    ->label('Acepta FOVISSSTE'),
            ])->columns(3),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagenes')
                    ->label('')
                    ->getStateUsing(fn ($record) => $record->imagen_principal)
                    ->width(70)->height(50),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors(['primary']),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->searchable(),

                Tables\Columns\TextColumn::make('municipio')
                    ->label('Municipio')
                    ->searchable(),

                Tables\Columns\TextColumn::make('precio_formateado')
                    ->label('Precio')
                    ->getStateUsing(fn ($record) => $record->precio_formateado),

                Tables\Columns\BadgeColumn::make('estatus')
                    ->label('Estatus')
                    ->colors([
                        'success' => 'en_venta',
                        'warning' => 'pausada',
                        'danger'  => 'vendida',
                    ])
                    ->formatStateUsing(fn ($state) => Propiedad::estatuses()[$state] ?? $state),

                Tables\Columns\IconColumn::make('destacada')
                    ->label('Destacada')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(Propiedad::tipos()),

                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options(Propiedad::estatuses()),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn () => Propiedad::distinct()->pluck('estado', 'estado')->sort()),

                Tables\Filters\TernaryFilter::make('destacada')
                    ->label('Destacada'),

                Tables\Filters\TernaryFilter::make('acepta_infonavit')
                    ->label('Acepta INFONAVIT'),

                Tables\Filters\TernaryFilter::make('acepta_fovissste')
                    ->label('Acepta FOVISSSTE'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPropiedads::route('/'),
            'create' => Pages\CreatePropiedad::route('/create'),
            'edit'   => Pages\EditPropiedad::route('/{record}/edit'),
        ];
    }
}
