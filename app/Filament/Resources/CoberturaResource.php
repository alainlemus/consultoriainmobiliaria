<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoberturaResource\Pages;
use App\Models\Cobertura;
use Filament\Forms;
use Filament\Schemas\Schema;
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
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Cobertura';
    protected static ?string $modelLabel      = 'Zona de cobertura';
    protected static ?string $pluralModelLabel = 'Cobertura';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Zona de cobertura')->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Estado / Nombre')
                    ->required()
                    ->maxLength(100)
                    ->validationMessages([
                        'required' => 'El nombre de la zona es obligatorio.',
                        'max'      => 'El nombre no puede superar los 100 caracteres.',
                    ]),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->rows(3)
                    ->validationMessages([
                        'required' => 'La descripción de la zona es obligatoria.',
                    ]),

                Forms\Components\TextInput::make('detalle')
                    ->label('Detalle (dirección o nota de contacto)')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->validationMessages([
                        'max' => 'El detalle no puede superar los 255 caracteres.',
                    ]),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Citas en esta oficina')
                ->description('Si está activo, los asesores y acreditados pueden agendar cita aquí desde la app. El super admin confirma cada cita. La dirección que ve el cliente es el campo «Detalle».')
                ->schema([
                    Forms\Components\Toggle::make('citas_activas')->label('Recibe citas')->default(false)->live(),
                    Forms\Components\CheckboxList::make('horario_citas.dias')->label('Días de atención')
                        ->options(['lun' => 'Lunes', 'mar' => 'Martes', 'mie' => 'Miércoles', 'jue' => 'Jueves', 'vie' => 'Viernes', 'sab' => 'Sábado', 'dom' => 'Domingo'])
                        ->default(['lun', 'mar', 'mie', 'jue', 'vie'])->columns(2)->columnSpanFull()->visible(fn ($get) => $get('citas_activas')),
                    Forms\Components\TimePicker::make('horario_citas.inicio')->label('Desde')->seconds(false)->default('09:00')->visible(fn ($get) => $get('citas_activas')),
                    Forms\Components\TimePicker::make('horario_citas.fin')->label('Hasta')->seconds(false)->default('17:00')->visible(fn ($get) => $get('citas_activas')),
                    Forms\Components\TextInput::make('horario_citas.duracion_min')->label('Duración cita en oficina (min)')->numeric()->default(60)->minValue(15)->maxValue(240)->visible(fn ($get) => $get('citas_activas')),
                    Forms\Components\TextInput::make('horario_citas.duracion_domicilio_min')->label('Duración visita a domicilio (min)')->numeric()->default(120)->minValue(30)->maxValue(480)->visible(fn ($get) => $get('citas_activas')),
                    Forms\Components\Toggle::make('horario_citas.permite_domicilio')->label('Ofrece visitas a domicilio')->default(true)->visible(fn ($get) => $get('citas_activas')),
                ])->columns(3),

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

                Tables\Columns\IconColumn::make('citas_activas')
                    ->label('Citas')
                    ->boolean()
                    ->tooltip('Recibe citas desde la app'),
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
            'index'  => Pages\ListCoberturas::route('/'),
            'create' => Pages\CreateCobertura::route('/create'),
            'edit'   => Pages\EditCobertura::route('/{record}/edit'),
        ];
    }
}
