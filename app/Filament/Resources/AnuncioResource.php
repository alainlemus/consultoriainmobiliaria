<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnuncioResource\Pages;
use App\Models\Anuncio;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AnuncioResource extends Resource
{
    protected static ?string $model = Anuncio::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel    = 'Anuncios';
    protected static ?string $modelLabel         = 'Anuncio';
    protected static ?string $pluralModelLabel   = 'Anuncios';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort     = 10;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('ViewAny:Anuncio') ?? false;
    }

    /**
     * Solo puede editar/retirar/reactivar quien tenga el permiso de edición
     * del recurso (los roles de solo lectura, como Revisor de Rutas, no).
     */
    public static function puedeEditar(): bool
    {
        return Auth::user()?->can('Update:Anuncio') ?? false;
    }

    /**
     * Roles con el permiso "Ver:TodosLosAsesores" ven los anuncios de todos
     * los asesores (columna/filtro "Asesor"); el resto solo los propios.
     */
    public static function puedeVerTodo(): bool
    {
        return Auth::user()?->can('Ver:TodosLosAsesores') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user:id,name', 'fotos']);
        if (! static::puedeVerTodo()) {
            $query->where('user_id', Auth::id());
        }
        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::where('estado', 'activo');
        if (! static::puedeVerTodo()) {
            $query->where('user_id', Auth::id());
        }
        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ubicación')->columns(2)->schema([
                Forms\Components\TextInput::make('latitud')
                    ->label('Latitud')->numeric()->required()->columnSpan(1),
                Forms\Components\TextInput::make('longitud')
                    ->label('Longitud')->numeric()->required()->columnSpan(1),
                Forms\Components\TextInput::make('direccion')
                    ->label('Dirección aproximada')->maxLength(500)->columnSpanFull(),
                Forms\Components\TextInput::make('colonia')
                    ->label('Colonia')->maxLength(150)->columnSpan(1),
                Forms\Components\TextInput::make('municipio')
                    ->label('Municipio')->maxLength(100)->columnSpan(1),
                Forms\Components\TextInput::make('estado_geo')
                    ->label('Estado')->maxLength(100)->columnSpan(1),
            ]),

            Section::make('Anuncio')->columns(2)->schema([
                Forms\Components\Select::make('tipo')
                    ->label('Tipo de anuncio')
                    ->options([
                        'lona'        => '📢 Lona',
                        'hoja_tienda' => '🏪 Hoja en tienda',
                        'hoja_poste'  => '📌 Hoja en poste',
                        'volante'     => '📄 Volante / reparto',
                        'otro'        => '📣 Otro',
                    ])
                    ->default('hoja_poste')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\Select::make('estado')
                    ->label('Estado')
                    ->options(['activo' => 'Activo', 'retirado' => 'Retirado'])
                    ->default('activo')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('colocado_en')
                    ->label('Fecha de colocación')
                    ->default(now())
                    ->required()
                    ->columnSpan(1),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción / notas')
                    ->rows(2)
                    ->maxLength(300)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('colocado_en', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => (Anuncio::TIPOS[$state]['emoji'] ?? '📣') . ' ' . (Anuncio::TIPOS[$state]['label'] ?? $state))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo'   => 'success',
                        'retirado' => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('municipio')
                    ->label('Municipio')
                    ->description(fn ($record) => collect([$record->colonia, $record->estado_geo])->filter()->implode(', '))
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Asesor')
                    ->sortable()
                    ->visible(fn () => static::puedeVerTodo()),

                Tables\Columns\TextColumn::make('colocado_en')
                    ->label('Colocado')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\ViewColumn::make('fotos')
                    ->label('Fotos')
                    ->view('filament.tables.columns.anuncio-fotos'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(array_combine(
                        array_keys(Anuncio::TIPOS),
                        array_map(fn ($t) => $t['emoji'] . ' ' . $t['label'], Anuncio::TIPOS)
                    )),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(['activo' => 'Activo', 'retirado' => 'Retirado']),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Asesor')
                    ->relationship('user', 'name')
                    ->visible(fn () => static::puedeVerTodo()),
            ])
            ->actions([
                \Filament\Actions\Action::make('ver_fotos')
                    ->label('Ver fotos')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn (Anuncio $record) => $record->fotos->isNotEmpty())
                    ->modalHeading(fn (Anuncio $record) => 'Fotos del anuncio — ' . ($record->municipio ?? $record->tipo))
                    ->modalContent(fn (Anuncio $record) => view('filament.modals.anuncio-fotos-carousel', [
                        'fotos' => $record->fotos->map(fn ($f) => \URL::signedRoute(
                            'api.anuncio.foto',
                            ['fotoId' => $f->id],
                            now()->addHour()->startOfHour()
                        ))->values(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                \Filament\Actions\Action::make('retirar')
                    ->label('Marcar retirado')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Anuncio $record) => $record->estado === 'activo' && static::puedeEditar())
                    ->action(fn (Anuncio $record) => $record->update(['estado' => 'retirado'])),

                \Filament\Actions\Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Anuncio $record) => $record->estado === 'retirado' && static::puedeEditar())
                    ->action(fn (Anuncio $record) => $record->update(['estado' => 'activo'])),

                \Filament\Actions\EditAction::make()
                    ->visible(fn () => static::puedeEditar()),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('retirar_bulk')
                        ->label('Marcar como retirados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['estado' => 'retirado'])),
                ])->visible(fn () => static::puedeEditar()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnuncios::route('/'),
            'edit'  => Pages\EditAnuncio::route('/{record}/edit'),
        ];
    }
}

