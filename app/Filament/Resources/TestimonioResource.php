<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonioResource\Pages;
use App\Models\Testimonio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonioResource extends Resource
{
    protected static ?string $model = Testimonio::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Testimonios';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int $navigationSort = 5;
    protected static ?string $modelLabel = 'Testimonio';
    protected static ?string $pluralModelLabel = 'Testimonios';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')->label('Nombre del cliente')->required(),
            Forms\Components\TextInput::make('ciudad')->label('Ciudad'),
            Forms\Components\Select::make('servicio')
                ->label('Servicio utilizado')
                ->options([
                    'INFONAVIT'  => 'INFONAVIT',
                    'FOVISSSTE'  => 'FOVISSSTE',
                    'Avalúo'     => 'Avalúo',
                    'Escrituras' => 'Escrituras',
                    'Asesoría'   => 'Asesoría',
                ]),
            Forms\Components\Select::make('estrellas')
                ->label('Calificación')
                ->options([5 => '⭐⭐⭐⭐⭐', 4 => '⭐⭐⭐⭐', 3 => '⭐⭐⭐'])
                ->default(5),
            Forms\Components\Textarea::make('testimonio')
                ->label('Testimonio')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('activo')->label('Visible en el sitio')->default(true),
            Forms\Components\TextInput::make('orden')->label('Orden de aparición')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('ciudad')->label('Ciudad'),
                Tables\Columns\BadgeColumn::make('servicio')->label('Servicio'),
                Tables\Columns\TextColumn::make('estrellas')->label('★')->sortable(),
                Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('orden')->label('Orden')->sortable(),
            ])
            ->reorderable('orden')
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTestimonios::route('/'),
            'create' => Pages\CreateTestimonio::route('/create'),
            'edit'   => Pages\EditTestimonio::route('/{record}/edit'),
        ];
    }
}
