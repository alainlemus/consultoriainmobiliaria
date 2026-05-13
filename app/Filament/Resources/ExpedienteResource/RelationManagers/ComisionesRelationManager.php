<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\Comision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ComisionesRelationManager extends RelationManager
{
    protected static string $relationship = 'comision';
    protected static ?string $title = 'Comisión';
    protected static ?string $label = 'Comisión';
    protected static ?string $pluralLabel = 'Comisiones';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aprobada'  => 'Aprobada',
                    'pagada'    => 'Pagada',
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

            Forms\Components\DatePicker::make('fecha_aprobacion')
                ->label('Fecha Aprobación'),

            Forms\Components\DatePicker::make('fecha_pago')
                ->label('Fecha Pago'),

            Forms\Components\Textarea::make('notas')
                ->label('Notas')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor'),

                Tables\Columns\TextColumn::make('monto_base')
                    ->label('Monto Base')
                    ->money('MXN'),

                Tables\Columns\TextColumn::make('porcentaje_comision')
                    ->label('%')
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('monto_comision')
                    ->label('Comisión')
                    ->money('MXN')
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
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Pagada')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->headerActions([
                // No se crean manualmente — se generan al cerrar expediente
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Comision $record) => $record->estado === 'pendiente' && Auth::user()?->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->action(fn (Comision $record) => $record->update([
                        'estado'           => 'aprobada',
                        'fecha_aprobacion' => now()->toDateString(),
                        'aprobado_por'     => Auth::id(),
                    ])),

                Tables\Actions\Action::make('marcar_pagada')
                    ->label('Pagada')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('primary')
                    ->visible(fn (Comision $record) => $record->estado === 'aprobada' && Auth::user()?->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->action(fn (Comision $record) => $record->update([
                        'estado'     => 'pagada',
                        'fecha_pago' => now()->toDateString(),
                    ])),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ]);
    }
}
