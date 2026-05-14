<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\DocumentoRequerido;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';
    protected static ?string $title = 'Checklist de Documentos';
    protected static ?string $modelLabel = 'Documento';
    protected static ?string $pluralModelLabel = 'Documentos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('documento_requerido_id')
                ->label('Documento requerido')
                ->options(function () {
                    $expediente = $this->getOwnerRecord();
                    return DocumentoRequerido::where('tipo_tramite_id', $expediente->tipo_tramite_id)
                        ->orderBy('seccion')
                        ->orderBy('orden')
                        ->get()
                        ->mapWithKeys(fn ($d) => [$d->id => "[{$d->seccion}] {$d->nombre}"]);
                })
                ->live()
                ->afterStateUpdated(function (Forms\Set $set, $state) {
                    if ($state) {
                        $doc = DocumentoRequerido::find($state);
                        if ($doc) {
                            $set('nombre', $doc->nombre);
                            $set('seccion', $doc->seccion);
                        }
                    }
                })
                ->searchable()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('nombre')
                ->label('Nombre del documento')
                ->required(),

            Forms\Components\Select::make('seccion')
                ->label('Sección')
                ->options([
                    'acreditado' => 'Acreditado',
                    'vendedor'   => 'Vendedor',
                    'vivienda'   => 'Vivienda',
                    'tramite'    => 'Trámite',
                ])
                ->required(),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente'  => 'Pendiente',
                    'entregado'  => 'Entregado',
                    'revisado'   => 'Revisado',
                    'aprobado'   => 'Aprobado',
                    'rechazado'  => 'Rechazado',
                ])
                ->default('pendiente')
                ->required(),

            Forms\Components\DatePicker::make('fecha_entrega')
                ->label('Fecha de entrega'),

            Forms\Components\FileUpload::make('ruta_archivo')
                ->label('Archivo')
                ->directory('documentos-expedientes')
                ->storeFileNamesIn('nombre_archivo')
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->maxSize(10240)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seccion')
                    ->label('Sección')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'acreditado' => 'primary',
                        'vendedor'   => 'warning',
                        'vivienda'   => 'success',
                        'tramite'    => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Documento')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'gray'    => 'pendiente',
                        'warning' => 'entregado',
                        'primary' => 'revisado',
                        'success' => 'aprobado',
                        'danger'  => 'rechazado',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pendiente'  => 'Pendiente',
                        'entregado'  => 'Entregado',
                        'revisado'   => 'Revisado',
                        'aprobado'   => 'Aprobado',
                        'rechazado'  => 'Rechazado',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('fecha_entrega')
                    ->label('Entregado')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('ruta_archivo')
                    ->label('Archivo')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('seccion')
                    ->options([
                        'acreditado' => 'Acreditado',
                        'vendedor'   => 'Vendedor',
                        'vivienda'   => 'Vivienda',
                        'tramite'    => 'Trámite',
                    ]),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'entregado'  => 'Entregado',
                        'revisado'   => 'Revisado',
                        'aprobado'   => 'Aprobado',
                        'rechazado'  => 'Rechazado',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                // Botón para pre-cargar checklist desde los documentos requeridos del tipo de trámite
                Tables\Actions\Action::make('cargar_checklist')
                    ->label('Cargar checklist del trámite')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Cargar checklist')
                    ->modalDescription('Se agregarán todos los documentos requeridos del tipo de trámite que aún no existan en el expediente. ¿Continuar?')
                    ->action(function () {
                        $expediente = $this->getOwnerRecord();
                        $requeridos = DocumentoRequerido::where('tipo_tramite_id', $expediente->tipo_tramite_id)
                            ->orderBy('seccion')
                            ->orderBy('orden')
                            ->get();

                        foreach ($requeridos as $req) {
                            $expediente->documentos()->firstOrCreate(
                                ['documento_requerido_id' => $req->id],
                                [
                                    'nombre'  => $req->nombre,
                                    'seccion' => $req->seccion,
                                    'estado'  => 'pendiente',
                                ]
                            );
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('seccion');
    }
}
