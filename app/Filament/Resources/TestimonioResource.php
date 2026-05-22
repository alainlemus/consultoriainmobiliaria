<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonioResource\Pages;
use App\Models\Testimonio;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonioResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = Testimonio::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Testimonios';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración del sitio';
    protected static ?int $navigationSort = 5;
    protected static ?string $modelLabel = 'Testimonio';
    protected static ?string $pluralModelLabel = 'Testimonios';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Testimonio del cliente')
                ->description('Los testimonios nuevos llegan con estado "Inactivo" desde el formulario público. Actívalos aquí para que aparezcan en la landing.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del cliente')
                        ->required()
                        ->maxLength(100)
                        ->hint('Nombre tal como aparecerá en la landing.')
                        ->placeholder('Ej. María González')
                        ->validationMessages([
                            'required' => 'El nombre del cliente es obligatorio.',
                            'max'      => 'El nombre no puede superar los 100 caracteres.',
                        ]),

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
                        ->hint('Calificación que le dio el cliente al servicio.')
                        ->validationMessages([
                            'required' => 'La calificación es obligatoria.',
                        ]),

                    Forms\Components\Textarea::make('testimonio')
                        ->label('Texto del testimonio')
                        ->required()
                        ->minLength(20)
                        ->maxLength(1000)
                        ->rows(5)
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'El texto del testimonio es obligatorio.',
                            'min'      => 'El testimonio debe tener al menos 20 caracteres.',
                            'max'      => 'El testimonio no puede superar los 1000 caracteres.',
                        ])
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
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
