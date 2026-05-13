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
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('expediente_id')
                        ->relationship('expediente', 'folio')
                        ->label('Expediente')
                        ->searchable()
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('asesor_id')
                        ->relationship('asesor', 'name')
                        ->label('Asesor')
                        ->searchable()
                        ->required()
                        ->disabled(),

                    Forms\Components\TextInput::make('monto_base')
                        ->label('Monto Base')
                        ->prefix('$')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\TextInput::make('porcentaje_comision')
                        ->label('Porcentaje (%)')
                        ->suffix('%')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\TextInput::make('monto_comision')
                        ->label('Monto Comisión')
                        ->prefix('$')
                        ->numeric()
                        ->disabled(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options([
                            'pendiente'  => 'Pendiente',
                            'aprobada'   => 'Aprobada',
                            'pagada'     => 'Pagada',
                        ])
                        ->required()
                        ->live()
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
                        ->disabled(),

                    Forms\Components\DatePicker::make('fecha_aprobacion')
                        ->label('Fecha Aprobación'),

                    Forms\Components\DatePicker::make('fecha_pago')
                        ->label('Fecha Pago'),

                    Forms\Components\Select::make('aprobado_por')
                        ->label('Aprobado Por')
                        ->options(User::all()->pluck('name', 'id'))
                        ->searchable(),
                ]),

            Forms\Components\Section::make('Notas')
                ->schema([
                    Forms\Components\Textarea::make('notas')
                        ->label('Notas')
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('porcentaje_comision')
                    ->label('%')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_comision')
                    ->label('Comisión')
                    ->money('MXN')
                    ->sortable()
                    ->weight('bold'),

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
                    }),

                Tables\Columns\TextColumn::make('fecha_generacion')
                    ->label('Generada')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Pagada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
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
