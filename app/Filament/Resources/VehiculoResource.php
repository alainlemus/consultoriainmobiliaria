<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoResource\Pages;
use App\Models\Vehiculo;
use App\Models\VehiculoReserva;
use App\Models\VehiculoTipo;
use App\Services\VehiculoService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** Vehículos de la empresa. El super admin los da de alta; los asesores los piden desde la app. */
class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Vehículos';

    protected static string|\UnitEnum|null $navigationGroup = 'Vehículos';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Vehículo';

    protected static ?string $pluralModelLabel = 'Vehículos';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function canDelete($record): bool
    {
        return $record->reservas()->doesntExist();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['tipo', 'llavesUsuario']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Vehículo')->schema([
                Forms\Components\Select::make('vehiculo_tipo_id')->label('Tipo')->required()
                    ->options(fn () => VehiculoTipo::where('activo', true)->pluck('nombre', 'id')),
                Forms\Components\TextInput::make('nombre')->label('Nombre corto')->required()->maxLength(60)->placeholder('Moto 1'),
                Forms\Components\TextInput::make('placa')->label('Placa')->maxLength(20),
                Forms\Components\TextInput::make('marca')->label('Marca')->maxLength(60),
                Forms\Components\TextInput::make('modelo')->label('Modelo')->maxLength(60),
                Forms\Components\TextInput::make('anio')->label('Año')->numeric()->minValue(1980)->maxValue(2100),
                Forms\Components\TextInput::make('color')->label('Color')->maxLength(30),
                Forms\Components\TextInput::make('capacidad_tanque_l')->label('Tanque (litros)')->numeric()->minValue(0),
                Forms\Components\Select::make('estado')->label('Estado')->options(Vehiculo::ESTADOS)->default(Vehiculo::ACTIVO)->required()
                    ->helperText('«En taller» o «Dado de baja» lo quitan de la app sin borrar su historial.'),
                Forms\Components\FileUpload::make('foto')->label('Foto')->image()->disk('public')->directory('vehiculos')->columnSpanFull(),
            ])->columns(3),

            Section::make('Kilometraje y documentos')->schema([
                Forms\Components\TextInput::make('km_actual')->label('Km actual')->numeric()->minValue(0)->default(0)
                    ->helperText('Se actualiza solo con cada salida y entrega.'),
                Forms\Components\TextInput::make('km_proximo_servicio')->label('Km del próximo servicio')->numeric()->minValue(0),
                Forms\Components\DatePicker::make('vence_seguro')->label('Vence el seguro'),
                Forms\Components\DatePicker::make('vence_tarjeta')->label('Vence tarjeta / verificación'),
                Forms\Components\Textarea::make('notas')->label('Notas')->rows(2)->columnSpanFull(),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')->label('')->disk('public')->circular(),
                Tables\Columns\TextColumn::make('nombre')->label('Vehículo')->weight('bold')->searchable()
                    ->description(fn (Vehiculo $r) => trim(($r->placa ?? '').' · '.$r->tipo?->nombre, ' ·')),
                Tables\Columns\TextColumn::make('uso')->label('Ahora')->badge()
                    ->getStateUsing(function (Vehiculo $r) {
                        if ($r->estado !== Vehiculo::ACTIVO) {
                            return Vehiculo::ESTADOS[$r->estado];
                        }
                        $u = app(VehiculoService::class)->reservaEnUso($r);
                        if (! $u) {
                            return 'Disponible';
                        }

                        return ($u->estado === VehiculoReserva::RESGUARDO ? 'En casa de ' : 'En uso · ').$u->asesor->name;
                    })
                    ->color(fn ($state) => str_starts_with($state, 'En ') && $state !== 'En taller' ? 'info' : ($state === 'Disponible' ? 'success' : 'gray')),
                Tables\Columns\TextColumn::make('llaves')->label('Llaves')
                    ->getStateUsing(fn (Vehiculo $r) => $r->llaves_en === 'oficina' ? 'En oficina' : ('Con '.($r->llavesUsuario?->name ?? '—'))),
                Tables\Columns\TextColumn::make('km_actual')->label('Km')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('gasolina_actual')->label('Gasolina')->formatStateUsing(fn ($state) => $state === null ? '—' : Vehiculo::GASOLINA[$state])->placeholder('—'),
                Tables\Columns\TextColumn::make('vence_seguro')->label('Seguro')->date('d/m/Y')->placeholder('—')
                    ->color(fn ($state) => $state && $state->lt(now()->addDays(15)) ? 'danger' : null)->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehiculo_tipo_id')->label('Tipo')->relationship('tipo', 'nombre'),
                Tables\Filters\SelectFilter::make('estado')->label('Estado')->options(Vehiculo::ESTADOS),
            ])
            ->actions([
                Action::make('codigo')->label('Código de llaves')->icon('heroicon-o-key')->color('warning')
                    ->modalDescription('Genera un código de 4 dígitos que dura '.VehiculoService::MIN_CODIGO.' minutos. Díctaselo a quien recibe las llaves para que lo escriba en su app.')
                    ->modalSubmitActionLabel('Generar código')
                    ->action(function (Vehiculo $record) {
                        $c = app(VehiculoService::class)->generarCodigo($record, Auth::user());
                        Notification::make()->title("Código de llaves: {$c['codigo']}")->body('Vence en '.VehiculoService::MIN_CODIGO.' minutos.')->success()->persistent()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehiculos::route('/'),
            'create' => Pages\CreateVehiculo::route('/create'),
            'edit' => Pages\EditVehiculo::route('/{record}/edit'),
        ];
    }
}
