<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FotoClienteResource\Pages;
use App\Models\FotoCliente;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FotoClienteResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?string $model = FotoCliente::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Fotos de Clientes';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración del sitio';
    protected static ?int $navigationSort = 6;
    protected static ?string $modelLabel = 'Foto de cliente';
    protected static ?string $pluralModelLabel = 'Fotos de Clientes';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Foto del cliente')
                ->description('Sube la foto del cliente con su crédito aprobado. Se mostrará como imagen cuadrada en el slider de la landing.')
                ->schema([
                    Forms\Components\FileUpload::make('foto')
                        ->label('Foto del cliente')
                        ->image()
                        ->imageEditor()
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('600')
                        ->imageResizeTargetHeight('600')
                        ->directory('fotos-clientes')
                        ->disk('public')
                        ->required()
                        ->columnSpanFull()
                        ->hint('Se recortará automáticamente en formato cuadrado 1:1.')
                        ->validationMessages([
                            'required' => 'La foto del cliente es obligatoria.',
                        ]),

                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del cliente')
                        ->maxLength(100)
                        ->placeholder('Ej. María González')
                        ->hint('Opcional. Se muestra al pasar el cursor.'),

                    Forms\Components\Select::make('tipo_credito')
                        ->label('Tipo de crédito')
                        ->options([
                            'INFONAVIT'                 => 'INFONAVIT',
                            'FOVISSSTE'                 => 'FOVISSSTE',
                            'Combo FOVISSSTE+INFONAVIT' => 'Combo FOVISSSTE+INFONAVIT',
                            'Avalúo'                    => 'Avalúo',
                            'Escrituras'                => 'Escrituras',
                            'Asesoría'                  => 'Asesoría',
                        ])
                        ->placeholder('— Seleccionar —')
                        ->hint('Se muestra como badge sobre la foto.'),

                    Forms\Components\TextInput::make('ciudad')
                        ->label('Ciudad')
                        ->maxLength(100)
                        ->placeholder('Ej. Pachuca, Hidalgo'),

                    Forms\Components\TextInput::make('orden')
                        ->label('Orden')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->hint('Número menor aparece primero.'),

                    Forms\Components\Toggle::make('activo')
                        ->label('Visible en el sitio')
                        ->default(true)
                        ->hint('Desactiva para ocultar sin eliminar.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->label('Foto')
                    ->disk('public')
                    ->square()
                    ->size(64),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('tipo_credito')
                    ->label('Crédito')
                    ->badge()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('orden')
                    ->label('Orden')
                    ->sortable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Visible')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subida')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Visibilidad')
                    ->trueLabel('Solo visibles')
                    ->falseLabel('Solo ocultas')
                    ->placeholder('Todas'),
            ])
            ->reorderable('orden')
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFotoClientes::route('/'),
            'create' => Pages\CreateFotoCliente::route('/create'),
            'edit'   => Pages\EditFotoCliente::route('/{record}/edit'),
        ];
    }
}
