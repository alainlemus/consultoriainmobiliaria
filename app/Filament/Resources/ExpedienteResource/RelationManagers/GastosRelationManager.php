<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GastosRelationManager extends RelationManager
{
    protected static string $relationship = 'gastos';
    protected static ?string $title = 'Gastos Financiados';
    protected static ?string $modelLabel = 'Gasto';
    protected static ?string $pluralModelLabel = 'Gastos';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('concepto')
                ->label('Concepto')
                ->required()
                ->columnSpanFull()
                ->validationMessages([
                    'required' => 'El concepto del gasto es obligatorio.',
                ]),

            Forms\Components\TextInput::make('monto')
                ->label('Monto')
                ->numeric()
                ->prefix('$')
                ->required()
                ->validationMessages([
                    'required' => 'El monto del gasto es obligatorio.',
                    'numeric'  => 'El monto debe ser un valor numérico.',
                ]),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'pagado'    => 'Pagado',
                    'cancelado' => 'Cancelado',
                ])
                ->default('pendiente')
                ->required()
                ->validationMessages([
                    'required' => 'El estado del gasto es obligatorio.',
                ]),

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
                \Filament\Actions\CreateAction::make()
                    ->after(function () {
                        // Recalcular total_gastos_financiados en el expediente
                        $expediente = $this->getOwnerRecord();
                        $total = $expediente->gastos()->where('estado', '!=', 'cancelado')->sum('monto');
                        $expediente->update(['total_gastos_financiados' => $total]);
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('ver_comprobante')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn ($record) => (bool) $record->comprobante_ruta)
                    ->modalHeading(fn ($record) => 'Comprobante — ' . $record->concepto)
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $url  = asset('storage/' . $record->comprobante_ruta);
                        $nombre = strtolower($record->comprobante_nombre ?? $record->comprobante_ruta);
                        $esPdf  = str_ends_with($nombre, '.pdf');

                        if ($esPdf) {
                            $html = '<iframe src="' . e($url) . '" style="width:100%;height:70vh;border:none;border-radius:6px;" title="Comprobante PDF"></iframe>';
                        } else {
                            $html = '<img src="' . e($url) . '" alt="Comprobante" style="max-width:100%;max-height:70vh;display:block;margin:0 auto;border-radius:6px;object-fit:contain;">';
                        }

                        return new \Illuminate\Support\HtmlString($html);
                    }),

                \Filament\Actions\EditAction::make()
                    ->after(function () {
                        $expediente = $this->getOwnerRecord();
                        $total = $expediente->gastos()->where('estado', '!=', 'cancelado')->sum('monto');
                        $expediente->update(['total_gastos_financiados' => $total]);
                    }),
                \Filament\Actions\DeleteAction::make()
                    ->after(function () {
                        $expediente = $this->getOwnerRecord();
                        $total = $expediente->gastos()->where('estado', '!=', 'cancelado')->sum('monto');
                        $expediente->update(['total_gastos_financiados' => $total]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
