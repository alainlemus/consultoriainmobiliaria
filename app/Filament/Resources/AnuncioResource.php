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
        $user = Auth::user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('asesor'));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user:id,name', 'fotos']);
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('user_id', Auth::id());
        }
        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::where('estado', 'activo');
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
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
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),

                Tables\Columns\TextColumn::make('colocado_en')
                    ->label('Colocado')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fotos_count')
                    ->label('Fotos')
                    ->counts('fotos')
                    ->badge()
                    ->color('info'),
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
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ])
            ->actions([
                \Filament\Actions\Action::make('retirar')
                    ->label('Marcar retirado')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Anuncio $record) => $record->estado === 'activo')
                    ->action(fn (Anuncio $record) => $record->update(['estado' => 'retirado'])),

                \Filament\Actions\Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Anuncio $record) => $record->estado === 'retirado')
                    ->action(fn (Anuncio $record) => $record->update(['estado' => 'activo'])),

                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('retirar_bulk')
                        ->label('Marcar como retirados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['estado' => 'retirado'])),
                ]),
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

