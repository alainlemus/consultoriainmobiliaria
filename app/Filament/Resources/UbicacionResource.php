<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UbicacionResource\Pages;
use App\Models\Ubicacion;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class UbicacionResource extends Resource
{
    protected static ?string $model = Ubicacion::class;

    protected static ?string $navigationIcon  = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Visitas / Propiedades';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $modelLabel      = 'Visita';
    protected static ?string $pluralModelLabel = 'Visitas y Propiedades';
    protected static ?int    $navigationSort  = 11;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('asesor'));
    }

    // Sin crear ni editar desde el admin — solo lectura
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        // El detalle se maneja en la página ViewUbicacion con blade propio
        return $infolist->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('visitado_en', 'desc')
            ->columns([
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'visita_cliente' => 'warning',
                        'propiedad'      => 'info',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'visita_cliente' => '🏠 Cliente',
                        'propiedad'      => '🏢 Propiedad',
                        default          => $state,
                    }),

                TextColumn::make('contacto.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('user.name')
                    ->label('Asesor')
                    ->searchable()
                    ->visible(fn () => auth()->user()?->hasRole('super_admin')),

                TextColumn::make('municipio')
                    ->label('Municipio')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('notas')
                    ->label('Notas')
                    ->limit(50)
                    ->placeholder('—')
                    ->tooltip(fn ($record) => $record->notas),

                // Fotos inline como miniaturas
                Tables\Columns\ViewColumn::make('fotos')
                    ->label('Fotos')
                    ->view('filament.tables.columns.ubicacion-fotos'),

                TextColumn::make('visitado_en')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'visita_cliente' => '🏠 Visita cliente',
                        'propiedad'      => '🏢 Propiedad',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Asesor')
                    ->options(fn () => User::role('asesor')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->visible(fn () => auth()->user()?->hasRole('super_admin')),

                Filter::make('municipio')
                    ->label('Municipio')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('municipio')->label('Municipio'),
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['municipio'], fn ($q, $v) => $q->where('municipio', 'like', "%{$v}%"))
                    ),

                Filter::make('estado')
                    ->label('Estado')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('estado')->label('Estado'),
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['estado'], fn ($q, $v) => $q->where('estado', 'like', "%{$v}%"))
                    ),

                Filter::make('con_fotos')
                    ->label('Con fotos')
                    ->query(fn (Builder $query) => $query->whereHas('fotos')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver detalle'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUbicacions::route('/'),
            'view'  => Pages\ViewUbicacion::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['contacto', 'user', 'fotos']);

        $user = auth()->user();
        if ($user && ! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}
