<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\EtapaTramite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SeguimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'seguimientos';
    protected static ?string $title = 'Bitácora de Seguimiento';
    protected static ?string $modelLabel = 'Seguimiento';
    protected static ?string $pluralModelLabel = 'Seguimientos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('tipo')
                ->label('Tipo de evento')
                ->options([
                    'nota'            => 'Nota interna',
                    'llamada'         => 'Llamada telefónica',
                    'reunion'         => 'Reunión',
                    'cambio_etapa'    => 'Cambio de etapa',
                    'documento'       => 'Documento recibido',
                    'pago'            => 'Pago registrado',
                    'alerta'          => 'Alerta',
                ])
                ->default('nota')
                ->required(),

            Forms\Components\Hidden::make('usuario_id')
                ->default(fn () => Auth::id()),

            Forms\Components\Select::make('etapa_anterior_id')
                ->label('Etapa anterior')
                ->options(fn () =>
                    EtapaTramite::where('tipo_tramite_id', $this->getOwnerRecord()->tipo_tramite_id)
                        ->orderBy('orden')
                        ->pluck('nombre', 'id')
                )
                ->nullable()
                ->searchable(),

            Forms\Components\Select::make('etapa_nueva_id')
                ->label('Nueva etapa')
                ->options(fn () =>
                    EtapaTramite::where('tipo_tramite_id', $this->getOwnerRecord()->tipo_tramite_id)
                        ->orderBy('orden')
                        ->pluck('nombre', 'id')
                )
                ->nullable()
                ->searchable(),

            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'gray'    => 'nota',
                        'info'    => 'llamada',
                        'primary' => 'reunion',
                        'warning' => 'cambio_etapa',
                        'success' => 'documento',
                        'success' => 'pago',
                        'danger'  => 'alerta',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'nota'         => 'Nota',
                        'llamada'      => 'Llamada',
                        'reunion'      => 'Reunión',
                        'cambio_etapa' => 'Cambio etapa',
                        'documento'    => 'Documento',
                        'pago'         => 'Pago',
                        'alerta'       => 'Alerta',
                        default        => $state,
                    }),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->limit(120),

                Tables\Columns\TextColumn::make('etapaAnterior.nombre')
                    ->label('Etapa anterior')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('etapaNueva.nombre')
                    ->label('Nueva etapa')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Registrado por')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'nota'         => 'Nota',
                        'llamada'      => 'Llamada',
                        'reunion'      => 'Reunión',
                        'cambio_etapa' => 'Cambio etapa',
                        'documento'    => 'Documento',
                        'pago'         => 'Pago',
                        'alerta'       => 'Alerta',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuario_id'] = Auth::id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
