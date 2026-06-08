<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\DocumentoExpediente;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\BulkAction;
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

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Documento')
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('ruta_archivo')
                ->label('Archivo del documento')
                ->disk('local')
                ->directory(fn ($record) => 'expedientes/' . ($record?->expediente_id ?? 'tmp') . '/docs')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(10240)
                ->columnSpanFull()
                ->helperText('PDF o imagen — máx. 10 MB. Al subir un archivo el estado cambia automáticamente a Recibido; al quitarlo vuelve a Pendiente.'),

            Forms\Components\TextInput::make('notas')
                ->label('Notas u observaciones')
                ->maxLength(255)
                ->placeholder('Opcional — ej: "pendiente firma", "copia simple aceptada"...')
                ->columnSpanFull(),
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
                \Filament\Actions\Action::make('marcar_recibido')
                    ->label('Recibido')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->estado !== 'recibido')
                    ->action(fn ($record) => $record->update(['estado' => 'recibido']))
                    ->requiresConfirmation(false),

                \Filament\Actions\Action::make('marcar_no_aplica')
                    ->label('No aplica')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn ($record) => $record->estado !== 'no_aplica')
                    ->action(fn ($record) => $record->update(['estado' => 'no_aplica']))
                    ->requiresConfirmation(false),

                \Filament\Actions\Action::make('marcar_pendiente')
                    ->label('Pendiente')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->estado !== 'pendiente')
                    ->action(fn ($record) => $record->update(['estado' => 'pendiente']))
                    ->requiresConfirmation(false),

                // Ver comprobante en modal
                \Filament\Actions\Action::make('ver_archivo')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn ($record) => (bool) $record->ruta_archivo)
                    ->modalHeading(fn ($record) => 'Documento — ' . $record->nombre)
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $url    = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                            'api.documentos.descargar',
                            now()->addMinutes(30),
                            ['expedienteId' => $record->expediente_id, 'documentoId' => $record->id]
                        );
                        $nombre = strtolower($record->ruta_archivo ?? '');
                        $esPdf  = str_ends_with($nombre, '.pdf');

                        if ($esPdf) {
                            // Botón principal + embed <object> (más fiable que <iframe> para PDFs)
                            $html = '
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <div style="text-align:center;">
                                        <a href="' . e($url) . '" target="_blank" rel="noopener"
                                           style="display:inline-flex;align-items:center;gap:6px;
                                                  background:#2563eb;color:#fff;padding:9px 20px;
                                                  border-radius:8px;text-decoration:none;
                                                  font-size:14px;font-weight:600;letter-spacing:.01em;">
                                            ↗&nbsp; Abrir PDF en nueva pestaña
                                        </a>
                                    </div>
                                    <object data="' . e($url) . '" type="application/pdf"
                                            style="width:100%;height:65vh;border:none;border-radius:6px;background:#f3f4f6;">
                                        <p style="text-align:center;color:#6b7280;padding:32px;font-size:14px;">
                                            Tu navegador no puede mostrar el PDF en línea.<br>
                                            <a href="' . e($url) . '" target="_blank"
                                               style="color:#2563eb;">Haz clic aquí para abrirlo</a>.
                                        </p>
                                    </object>
                                </div>';
                        } else {
                            $html = '<img src="' . e($url) . '" alt="Documento"
                                         style="max-width:100%;max-height:70vh;display:block;
                                                margin:0 auto;border-radius:6px;object-fit:contain;">';
                        }

                        return new \Illuminate\Support\HtmlString($html);
                    }),

                // Editar notas y subir/reemplazar archivo
                \Filament\Actions\EditAction::make()
                    ->label('Editar / Subir')
                    ->icon('heroicon-o-paper-clip')
                    ->mutateRecordDataUsing(fn (array $data) => $data)
                    ->using(function (Model $record, array $data): Model {
                        $archivoNuevo   = $data['ruta_archivo'] ?? null;
                        $archivoActual  = $record->ruta_archivo;

                        if ($archivoNuevo && $archivoNuevo !== $archivoActual) {
                            // Se subió un archivo nuevo → recibido
                            $data['estado'] = 'recibido';
                        } elseif (! $archivoNuevo && $archivoActual) {
                            // Se eliminó el archivo → pendiente
                            $data['estado'] = 'pendiente';
                        }

                        $record->update($data);
                        return $record;
                    }),
            ])
            ->bulkActions([
                BulkAction::make('marcar_recibidos')
                    ->label('Marcar seleccionados como recibidos')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($records) => $records->each->update(['estado' => 'recibido'])),

                BulkAction::make('marcar_no_aplica_bulk')
                    ->label('Marcar como no aplica')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->action(fn ($records) => $records->each->update(['estado' => 'no_aplica'])),
            ]);
    }
}
