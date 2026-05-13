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
            Forms\Components\Section::make('Testimonio del cliente')
                ->description('Los testimonios nuevos llegan con estado "Inactivo" desde el formulario público. Actívalos aquí para que aparezcan en la landing.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del cliente')
                        ->required()
                        ->maxLength(100)
                        ->hint('Nombre tal como aparecerá en la landing.')
                        ->placeholder('Ej. María González'),

                    Forms\Components\TextInput::make('ciudad')
                        ->label('Ciudad / Estado')
                        ->maxLength(100)
                        ->hint('Opcional. Se muestra debajo del nombre.')
                        ->placeholder('Ej. Pachuca, Hidalgo'),

                    Forms\Components\Select::make('servicio')
                        ->label('Servicio utilizado')
                        ->options([
                            'INFONAVIT'                  => 'INFONAVIT',
                            'FOVISSSTE'                  => 'FOVISSSTE',
                            'Combo FOVISSSTE+INFONAVIT'  => 'Combo FOVISSSTE+INFONAVIT',
                            'Avalúo'                     => 'Avalúo',
                            'Escrituras'                 => 'Escrituras',
                            'Asesoría general'           => 'Asesoría general',
                        ])
                        ->hint('Selecciona el servicio que usó el cliente.')
                        ->placeholder('— Seleccionar —'),

                    Forms\Components\Select::make('estrellas')
                        ->label('Calificación')
                        ->options([
                            5 => '★★★★★  Excelente',
                            4 => '★★★★☆  Muy bueno',
                            3 => '★★★☆☆  Bueno',
                            2 => '★★☆☆☆  Regular',
                            1 => '★☆☆☆☆  Malo',
                        ])
                        ->default(5)
                        ->required()
                        ->hint('Calificación que le dio el cliente al servicio.'),

                    Forms\Components\Textarea::make('testimonio')
                        ->label('Texto del testimonio')
                        ->required()
                        ->minLength(20)
                        ->maxLength(1000)
                        ->rows(5)
                        ->columnSpanFull()
                        ->hint('Mínimo 20 caracteres, máximo 1000.')
                        ->placeholder('Cuéntanos cómo fue el proceso, qué servicio usaste y cómo te ayudamos…'),

                    Forms\Components\Toggle::make('activo')
                        ->label('Visible en el sitio web')
                        ->default(false)
                        ->hint('Activa para que aparezca en la sección de testimonios de la landing.'),

                    Forms\Components\TextInput::make('orden')
                        ->label('Orden de aparición')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->hint('Número menor aparece primero. 0 = más reciente primero.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('servicio')
                    ->label('Servicio')
                    ->badge()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('estrellas')
                    ->label('★')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state) . str_repeat('☆', 5 - (int) $state))
                    ->color('warning'),

                Tables\Columns\TextColumn::make('testimonio')
                    ->label('Testimonio')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->testimonio)
                    ->wrap(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Visible')
                    ->boolean()
                    ->tooltip('Activo = se muestra en la landing'),

                Tables\Columns\TextColumn::make('orden')
                    ->label('Orden')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Visibilidad')
                    ->trueLabel('Solo visibles')
                    ->falseLabel('Solo ocultos')
                    ->placeholder('Todos'),
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
