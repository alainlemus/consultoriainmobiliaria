<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComisionResource\Pages;
use App\Models\Comision;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ComisionResource extends Resource
{
    protected static ?string $model = Comision::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Comisiones';
    protected static ?string $modelLabel = 'Comisión';
    protected static ?string $pluralModelLabel = 'Comisiones';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 5;

    public static function getGloballySearchableAttributes(): array
    {
        return ['expediente.folio', 'asesor.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Comisión — ' . ($record->expediente?->folio ?? 'EXP-???');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Asesor' => $record->asesor?->name ?? '—',
            'Monto'  => '$' . number_format($record->monto_comision, 2) . ' MXN',
            'Estado' => ucfirst($record->estado),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function canCreate(): bool
    {
        return false; // Se crean automáticamente al cerrar expediente
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $query = Comision::where('estado', 'pendiente');
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }
        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de la Comisión')
                ->description('Las comisiones se generan automáticamente al cerrar un expediente. Los campos deshabilitados son de solo lectura. Solo el administrador puede cambiar el estado y aprobar el pago.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('expediente_id')
                        ->relationship('expediente', 'folio')
                        ->label('Expediente')
                        ->searchable()
                        ->required()
                        ->disabled()
                        ->hint('Expediente que originó esta comisión'),

                    Forms\Components\Select::make('asesor_id')
                        ->relationship('asesor', 'name')
                        ->label('Asesor')
                        ->searchable()
                        ->required()
                        ->disabled()
                        ->hint('Asesor que gestionó el expediente'),

                    Forms\Components\TextInput::make('monto_base')
                        ->label('Monto Base')
                        ->prefix('$')
                        ->numeric()
                        ->disabled()
                        ->hint('Honorarios cobrados al cliente (base de cálculo)'),

                    Forms\Components\TextInput::make('porcentaje_comision')
                        ->label('Porcentaje (%)')
                        ->suffix('%')
                        ->numeric()
                        ->disabled()
                        ->hint('Porcentaje aplicado sobre el monto base'),

                    Forms\Components\TextInput::make('monto_comision')
                        ->label('Monto Comisión')
                        ->prefix('$')
                        ->numeric()
                        ->disabled()
                        ->hint('Monto final a pagar al asesor'),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'pendiente'  => 'Pendiente',
                            'aprobada'   => 'Aprobada',
                            'pagada'     => 'Pagada',
                        ])
                        ->required()
                        ->live()
                        ->hint('Pendiente → Aprobada → Pagada')
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === 'aprobada') {
                                $set('fecha_aprobacion', now()->toDateString());
                                $set('aprobado_por', Auth::id());
                            }
                            if ($state === 'pagada') {
                                $set('fecha_pago', now()->toDateString());
                            }
                        }),

                    Forms\Components\DatePicker::make('fecha_generacion')
                        ->label('Fecha Generación')
                        ->disabled()
                        ->hint('Fecha en que se cerró el expediente'),

                    Forms\Components\DatePicker::make('fecha_aprobacion')
                        ->label('Fecha Aprobación')
                        ->hint('Se rellena al aprobar la comisión'),

                    Forms\Components\DatePicker::make('fecha_pago')
                        ->label('Fecha Pago')
                        ->hint('Fecha real en que se realizó el pago al asesor'),

                    Forms\Components\Select::make('aprobado_por')
                        ->label('Aprobado Por')
                        ->options(User::all()->pluck('name', 'id'))
                        ->searchable()
                        ->hint('Administrador que autorizó el pago'),
                ]),

            Forms\Components\Section::make('Notas')
                ->description('Observaciones sobre el pago: método de pago, referencia de transferencia, etc.')
                ->schema([
                    Forms\Components\Textarea::make('notas')
                        ->label('Notas')
                        ->placeholder('Ej: Transferencia SPEI ref. 123456 realizada el 13/05/2026')
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expediente.folio')
                    ->label('Expediente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_base')
                    ->label('Monto Base')
                    ->money('MXN')
                    ->sortable()
                    ->tooltip('Honorarios cobrados al cliente — base de cálculo de la comisión'),

                Tables\Columns\TextColumn::make('porcentaje_comision')
                    ->label('%')
                    ->suffix('%')
                    ->sortable()
                    ->tooltip('Porcentaje del monto base que corresponde al asesor'),

                Tables\Columns\TextColumn::make('monto_comision')
                    ->label('Comisión')
                    ->money('MXN')
                    ->sortable()
                    ->weight('bold')
                    ->tooltip('Monto final a pagar al asesor'),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'aprobada',
                        'primary' => 'pagada',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'aprobada'  => 'Aprobada',
                        'pagada'    => 'Pagada',
                        default     => $state,
                    })
                    ->tooltip('Flujo: Pendiente → Aprobada → Pagada'),

                Tables\Columns\TextColumn::make('fecha_generacion')
                    ->label('Generada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->tooltip('Fecha en que se cerró el expediente'),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Pagada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—')
                    ->tooltip('Fecha real en que se realizó el pago al asesor'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobada'  => 'Aprobada',
                        'pagada'    => 'Pagada',
                    ]),

                Tables\Filters\SelectFilter::make('asesor_id')
                    ->label('Asesor')
                    ->relationship('asesor', 'name')
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Comision $record) => $record->estado === 'pendiente' && Auth::user()?->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->action(function (Comision $record) {
                        $record->update([
                            'estado'            => 'aprobada',
                            'fecha_aprobacion'  => now()->toDateString(),
                            'aprobado_por'      => Auth::id(),
                        ]);
                    }),

                Tables\Actions\Action::make('marcar_pagada')
                    ->label('Marcar Pagada')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('primary')
                    ->visible(fn (Comision $record) => $record->estado === 'aprobada' && Auth::user()?->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->action(function (Comision $record) {
                        $record->update([
                            'estado'      => 'pagada',
                            'fecha_pago'  => now()->toDateString(),
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComisiones::route('/'),
            'edit'  => Pages\EditComision::route('/{record}/edit'),
        ];
    }
}
