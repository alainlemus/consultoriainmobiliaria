<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\DocumentoExpediente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';
    protected static ?string $title       = 'Checklist de documentos';
    protected static ?string $modelLabel  = 'documento';

    // Asesor puede ver y editar estado/notas/archivo, pero no crear ni eliminar
    public function canCreate(): bool { return false; }
    public function canDelete(Model $record): bool   { return Auth::user()?->hasRole('super_admin') ?? false; }
    public function canDeleteAny(): bool             { return Auth::user()?->hasRole('super_admin') ?? false; }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Documento')
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->required()
                ->options([
                    'pendiente' => 'Pendiente',
                    'recibido'  => 'Recibido',
                    'no_aplica' => 'No aplica',
                ])
                ->default('pendiente'),

            Forms\Components\TextInput::make('notas')
                ->label('Notas')
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('ruta_archivo')
                ->label('Archivo del documento')
                ->disk('public')
                ->directory('documentos-expediente')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(10240) // 10 MB
                ->downloadable()
                ->openable()
                ->columnSpanFull()
                ->helperText('PDF o imagen — máx. 10 MB')
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    // Si se sube archivo, marcar como recibido automáticamente
                    if ($state) {
                        $set('estado', 'recibido');
                    }
                })
                ->live(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Checklist de documentos')
            ->description('Marca cada documento conforme sea recibido. Los documentos se generan automáticamente según el tipo de trámite e inmueble.')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Documento')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'danger'  => 'pendiente',
                        'success' => 'recibido',
                        'gray'    => 'no_aplica',
                    ])
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'recibido'  => 'Recibido ✓',
                        'no_aplica' => 'No aplica',
                        default     => 'Pendiente',
                    }),

                Tables\Columns\IconColumn::make('ruta_archivo')
                    ->label('Archivo')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->ruta_archivo ? 'Archivo adjunto' : 'Sin archivo'),

                Tables\Columns\TextColumn::make('notas')
                    ->label('Notas')
                    ->placeholder('—')
                    ->limit(35),
            ])
            ->defaultSort('nombre')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'recibido'  => 'Recibido',
                        'no_aplica' => 'No aplica',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                // Acciones rápidas de estado (un clic, sin modal)
                Tables\Actions\Action::make('marcar_recibido')
                    ->label('Recibido')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->estado !== 'recibido')
                    ->action(fn ($record) => $record->update(['estado' => 'recibido']))
                    ->requiresConfirmation(false),

                Tables\Actions\Action::make('marcar_no_aplica')
                    ->label('No aplica')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn ($record) => $record->estado !== 'no_aplica')
                    ->action(fn ($record) => $record->update(['estado' => 'no_aplica']))
                    ->requiresConfirmation(false),

                Tables\Actions\Action::make('marcar_pendiente')
                    ->label('Pendiente')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->estado !== 'pendiente')
                    ->action(fn ($record) => $record->update(['estado' => 'pendiente']))
                    ->requiresConfirmation(false),

                // Ver / descargar archivo
                Tables\Actions\Action::make('ver_archivo')
                    ->label('Ver archivo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn ($record) => (bool) $record->ruta_archivo)
                    ->url(fn ($record) => Storage::disk('public')->url($record->ruta_archivo))
                    ->openUrlInNewTab(),

                // Editar notas y subir/reemplazar archivo
                Tables\Actions\EditAction::make()
                    ->label('Editar / Subir')
                    ->icon('heroicon-o-paper-clip')
                    ->mutateRecordDataUsing(fn (array $data) => $data)
                    ->using(function (Model $record, array $data): Model {
                        // Si se subió un archivo, estado pasa a recibido
                        if (!empty($data['ruta_archivo']) && $data['ruta_archivo'] !== $record->ruta_archivo) {
                            $data['estado'] = 'recibido';
                        }
                        $record->update($data);
                        return $record;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('marcar_recibidos')
                    ->label('Marcar seleccionados como recibidos')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($records) => $records->each->update(['estado' => 'recibido'])),

                Tables\Actions\BulkAction::make('marcar_no_aplica_bulk')
                    ->label('Marcar como no aplica')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->action(fn ($records) => $records->each->update(['estado' => 'no_aplica'])),
            ]);
    }
}
