<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CodigoQrResource\Pages;
use App\Models\CodigoQr;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Códigos QR por asesor y medio para publicidad impresa.
 * super_admin los crea y ve el embudo de todos; el asesor solo ve y descarga los suyos.
 */
class CodigoQrResource extends Resource
{
    protected static ?string $model = CodigoQr::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'Códigos QR';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 5;
    protected static ?string $modelLabel = 'Código QR';
    protected static ?string $pluralModelLabel = 'Códigos QR';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'asesor']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('asesor')
            ->withCount([
                'escaneos', 'acreditados', 'contactos', 'expedientes',
                // Instalaciones donde el código llegó solo (Play Store o cruce por huella)
                'acreditados as acreditados_auto_count' => fn ($q) => $q->whereIn('asesor_origen', ['referrer', 'huella']),
            ]);

        if (Auth::user()?->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('QR para publicidad impresa')
                ->description('Lo normal es un QR por asesor con medio «Todos los medios», que se imprime en lona, tarjeta, volante… Si quieres medir un medio por separado, crea otro QR del mismo asesor con ese medio. El código corto se imprime junto al QR: quien no pueda escanear lo escribe al crear su cuenta en la app y queda ligado al asesor.')
                ->schema([
                    Forms\Components\Select::make('asesor_id')
                        ->label('Asesor')
                        ->options(User::where('activo', true)->role('asesor')->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->validationMessages(['required' => 'Elige el asesor dueño de este QR.']),

                    Forms\Components\Select::make('medio')
                        ->label('Medio donde se imprime')
                        ->options(CodigoQr::MEDIOS)
                        ->default('todos')
                        ->required()
                        ->hint('Déjalo en «Todos los medios» salvo que quieras medir un medio aparte.'),

                    Forms\Components\TextInput::make('descripcion')
                        ->label('Descripción / ubicación')
                        ->placeholder('Ej. Lona en la plaza principal de Huejutla')
                        ->maxLength(150)
                        ->hint('Opcional. Para reconocerlo en la lista.'),

                    Forms\Components\TextInput::make('codigo')
                        ->label('Código corto')
                        ->default(fn () => CodigoQr::generarCodigo())
                        ->required()
                        ->minLength(4)
                        ->maxLength(10)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase; letter-spacing: 2px; font-weight: 600;'])
                        // Normalizar al salir del campo para que la validación de unicidad
                        // compare ya en mayúsculas y sin espacios (igual que lo guarda).
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('codigo', CodigoQr::normalizar($state)))
                        ->dehydrateStateUsing(fn ($state) => CodigoQr::normalizar($state))
                        ->rule('regex:/^[A-Za-z0-9 -]{4,12}$/')
                        ->rule(fn ($record) => Rule::unique('codigos_qr', 'codigo')->ignore($record?->id))
                        ->hint('Se genera solo. Puedes cambiarlo (letras y números, 4 a 10). Ya impreso, no lo cambies.')
                        ->validationMessages([
                            'required' => 'El código es obligatorio.',
                            'regex'    => 'Solo letras y números, de 4 a 10 caracteres.',
                            'unique'   => 'Ese código ya existe. Usa otro.',
                        ]),

                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true)
                        ->hint('Pausado: el QR manda a la página de descarga sin contar ni asignar.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->searchable(),

                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('medio')
                    ->label('Medio')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CodigoQr::MEDIOS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'todos'    => 'gray',
                        'lona'     => 'warning',
                        'tarjeta'  => 'info',
                        'panfleto' => 'success',
                        'anuncio'  => 'primary',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->descripcion)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('escaneos_count')
                    ->label('Escaneos')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip('Veces que se abrió la liga del QR'),

                Tables\Columns\TextColumn::make('acreditados_count')
                    ->label('Registros')
                    ->sortable()
                    ->alignCenter()
                    ->description(fn ($record) => $record->acreditados_auto_count > 0 ? "{$record->acreditados_auto_count} detectados solos" : null)
                    ->tooltip('Cuentas creadas en la app con este código. "Detectados solos": el código llegó sin teclearlo (Play Store o cruce por huella).'),

                Tables\Columns\TextColumn::make('contactos_count')
                    ->label('Solicitudes')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip('Prospectos que pidieron asesoría'),

                Tables\Columns\TextColumn::make('expedientes_count')
                    ->label('Expedientes')
                    ->sortable()
                    ->alignCenter()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->tooltip('Expedientes abiertos a partir de esos prospectos'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Liga')
                    ->copyable()
                    ->copyMessage('Liga copiada')
                    ->limit(32)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('asesor_id')
                    ->label('Asesor')
                    ->options(User::role('asesor')->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
                Tables\Filters\SelectFilter::make('medio')
                    ->label('Medio')
                    ->options(CodigoQr::MEDIOS),
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo pausados')
                    ->placeholder('Todos'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('png')
                        ->label('PNG (tarjetas, panfletos)')
                        ->icon('heroicon-o-photo')
                        ->url(fn (CodigoQr $record) => route('qr.asesor.png', $record), shouldOpenInNewTab: true),
                    \Filament\Actions\Action::make('svg')
                        ->label('SVG (lonas, imprenta)')
                        ->icon('heroicon-o-arrows-pointing-out')
                        ->url(fn (CodigoQr $record) => route('qr.asesor.svg', $record), shouldOpenInNewTab: true),
                    \Filament\Actions\Action::make('pdf')
                        ->label('PDF con código')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (CodigoQr $record) => route('qr.asesor.pdf', $record), shouldOpenInNewTab: true),
                ])
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->button(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->modalDescription('Los registros y prospectos ya ligados conservan su asesor; solo se pierde el conteo del QR. Si ya está impreso, mejor páusalo.'),
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
            'index'  => Pages\ListCodigosQr::route('/'),
            'create' => Pages\CreateCodigoQr::route('/create'),
            'edit'   => Pages\EditCodigoQr::route('/{record}/edit'),
        ];
    }
}
