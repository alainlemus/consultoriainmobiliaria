<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GastosRelationManager extends RelationManager
{
    protected static string $relationship = 'gastos';
    protected static ?string $title = 'Gastos Financiados';
    protected static ?string $modelLabel = 'Gasto';
    protected static ?string $pluralModelLabel = 'Gastos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('concepto')
                ->label('Concepto')
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('monto')
                ->label('Monto')
                ->numeric()
                ->prefix('$')
                ->required(),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'pagado'    => 'Pagado',
                    'cancelado' => 'Cancelado',
                ])
                ->default('pendiente')
                ->required(),

            Forms\Components\DatePicker::make('fecha_pago')
                ->label('Fecha de pago'),

            Forms\Components\FileUpload::make('comprobante_ruta')
                ->label('Comprobante')
                ->directory('comprobantes-gastos')
                ->storeFileNamesIn('comprobante_nombre')
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->maxSize(10240)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('notas')
                ->label('Notas')
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('concepto')
                    ->label('Concepto')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('MXN')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'pagado',
                        'danger'  => 'cancelado',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pendiente' => 'Pendiente',
                        'pagado'    => 'Pagado',
                        'cancelado' => 'Cancelado',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Fecha pago')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('comprobante_ruta')
                    ->label('Comprobante')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'pagado'    => 'Pagado',
                        'cancelado' => 'Cancelado',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function () {
                        // Recalcular total_gastos_financiados en el expediente
                        $expediente = $this->getOwnerRecord();
                        $total = $expediente->gastos()->where('estado', '!=', 'cancelado')->sum('monto');
                        $expediente->update(['total_gastos_financiados' => $total]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        $expediente = $this->getOwnerRecord();
                        $total = $expediente->gastos()->where('estado', '!=', 'cancelado')->sum('monto');
                        $expediente->update(['total_gastos_financiados' => $total]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $expediente = $this->getOwnerRecord();
                        $total = $expediente->gastos()->where('estado', '!=', 'cancelado')->sum('monto');
                        $expediente->update(['total_gastos_financiados' => $total]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
