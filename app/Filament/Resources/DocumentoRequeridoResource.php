<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentoRequeridoResource\Pages;
use App\Models\DocumentoRequerido;
use App\Models\TipoTramite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentoRequeridoResource extends Resource
{
    protected static ?string $model = DocumentoRequerido::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Documentos Requeridos';
    protected static ?string $modelLabel = 'Documento Requerido';
    protected static ?string $pluralModelLabel = 'Documentos Requeridos';
    protected static ?string $navigationGroup = 'Configuración CRM';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('tipo_tramite_id')
                ->label('Tipo de Trámite')
                ->options(TipoTramite::pluck('nombre', 'id'))
                ->required()
                ->searchable(),
            Forms\Components\Select::make('seccion')
                ->label('Sección')
                ->options([
                    'acreditado' => 'Acreditado (cliente)',
                    'vendedor'   => 'Vendedor / Propietario',
                    'vivienda'   => 'Vivienda / Propiedad',
                ])
                ->required(),
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre del Documento')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción / Instrucciones')
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('orden')
                ->label('Orden')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('obligatorio')
                ->label('Obligatorio')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('orden')
                    ->label('#')
                    ->sortable()
                    ->width(60),
                Tables\Columns\TextColumn::make('tipoTramite.nombre')
                    ->label('Trámite')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('seccion')
                    ->label('Sección')
                    ->colors([
                        'primary' => 'acreditado',
                        'warning' => 'vendedor',
                        'success' => 'vivienda',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'acreditado' => 'Acreditado',
                        'vendedor'   => 'Vendedor',
                        'vivienda'   => 'Vivienda',
                        default      => $state,
                    }),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Documento')
                    ->searchable(),
                Tables\Columns\IconColumn::make('obligatorio')
                    ->label('Obligatorio')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->groups(['tipoTramite.nombre', 'seccion'])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocumentoRequeridos::route('/'),
            'create' => Pages\CreateDocumentoRequerido::route('/create'),
            'edit'   => Pages\EditDocumentoRequerido::route('/{record}/edit'),
        ];
    }
}
