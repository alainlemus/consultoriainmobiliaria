<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicioResource\Pages;
use App\Models\Servicio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServicioResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = Servicio::class;
    protected static ?string $navigationIcon  = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Servicios';
    protected static ?string $modelLabel      = 'Servicio';
    protected static ?string $pluralModelLabel = 'Servicios';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del servicio')->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('icon_path')
                    ->label('SVG path del ícono (heroicon outline)')
                    ->helperText('Copia el valor del atributo "d" del <path> del ícono SVG.')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('wa_texto')
                    ->label('Texto para enlace WhatsApp')
                    ->helperText('Ej: crédito INFONAVIT — se usará en el mensaje predefinido de WhatsApp.')
                    ->required()
                    ->maxLength(100)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Beneficios / Lista de ítems')->schema([
                Forms\Components\Repeater::make('items')
                    ->label('Ítems')
                    ->simple(
                        Forms\Components\TextInput::make('item')
                            ->label('Ítem')
                            ->required()
                    )
                    ->addActionLabel('Agregar ítem')
                    ->minItems(1)
                    ->maxItems(8)
                    ->reorderable()
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Visibilidad')->schema([
                Forms\Components\Toggle::make('activo')
                    ->label('Visible en el sitio')
                    ->default(true),

                Forms\Components\TextInput::make('orden')
                    ->label('Orden de aparición')
                    ->numeric()
                    ->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('orden')
            ->columns([
                Tables\Columns\TextColumn::make('orden')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Servicio')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('items')
                    ->label('Ítems')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' ítem(s)' : '-'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('orden');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServicios::route('/'),
            'create' => Pages\CreateServicio::route('/create'),
            'edit'   => Pages\EditServicio::route('/{record}/edit'),
        ];
    }
}
