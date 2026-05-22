<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\Comision;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
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

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aprobada'  => 'Aprobada',
                    'pagada'    => 'Pagada',
                ])
                ->required()
                ->live()
                ->validationMessages([
                    'required' => 'El estado de la comisión es obligatorio.',
                ])
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state === 'aprobada') {
                        $set('fecha_aprobacion', now()->toDateString());
                        $set('aprobado_por', Auth::id());
                    }
                    if ($state === 'pagada') {
                        $set('fecha_pago', now()->toDateString());
                    }
                }),

            Forms\Components\DatePicker::make('fecha_aprobacion')
                ->label('Fecha Aprobación')
                ->beforeOrEqual('today')
                ->validationMessages([
                    'before_or_equal' => 'La fecha de aprobación no puede ser futura.',
                ]),

            Forms\Components\DatePicker::make('fecha_pago')
                ->label('Fecha Pago')
                ->beforeOrEqual('today')
                ->validationMessages([
                    'before_or_equal' => 'La fecha de pago no puede ser futura.',
                ]),

            Forms\Components\Textarea::make('notas')
                ->label('Notas')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                if (Auth::user()?->hasRole('asesor')) {
                    $query->where('asesor_id', Auth::id());
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),

                Tables\Columns\TextColumn::make('monto_comision')
                    ->label('Mi comisión')
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
            ])
            ->actions([
                \Filament\Actions\Action::make('aprobar')
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

                \Filament\Actions\Action::make('marcar_pagada')
                    ->label('Pagada')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('primary')
                    ->visible(fn (Comision $record) => $record->estado === 'aprobada' && Auth::user()?->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->action(fn (Comision $record) => $record->update([
                        'estado'     => 'pagada',
                        'fecha_pago' => now()->toDateString(),
                    ])),

                \Filament\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ]);
    }
}