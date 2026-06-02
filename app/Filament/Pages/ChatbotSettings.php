<?php

namespace App\Filament\Pages;

use App\Models\ChatbotPaso;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ChatbotSettings extends Page implements HasTable
{
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationLabel = 'Flujo del Chatbot';
    protected static ?string $title           = 'Configuración del Chatbot WhatsApp';
    protected static string|\UnitEnum|null $navigationGroup  = 'Configuración del sitio';
    protected static ?int $navigationSort     = 51;
    protected string $view = 'filament.pages.chatbot-settings';

    public function table(Table $table): Table
    {
        return $table
            ->query(ChatbotPaso::query()->orderBy('orden'))
            ->reorderable('orden')
            ->columns([
                Tables\Columns\TextColumn::make('orden')
                    ->label('#')
                    ->width(40)
                    ->sortable(),

                Tables\Columns\TextColumn::make('etiqueta')
                    ->label('Paso')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'mensaje',
                        'warning' => 'seleccion',
                        'success' => 'texto_libre',
                        'gray'    => 'condicional',
                    ]),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('requerido')
                    ->label('Requerido')
                    ->boolean(),

                Tables\Columns\TextColumn::make('mensaje')
                    ->label('Mensaje')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->mensaje),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formPaso())
                    ->after(fn () => cache()->forget('chatbot_pasos_activos')),

                Action::make('toggle')
                    ->label(fn ($record) => $record->activo ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->activo ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->activo ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update(['activo' => ! $record->activo]);
                        cache()->forget('chatbot_pasos_activos');
                        Notification::make()
                            ->title($record->activo ? 'Paso activado.' : 'Paso desactivado.')
                            ->success()->send();
                    }),

                DeleteAction::make()
                    ->after(fn () => cache()->forget('chatbot_pasos_activos')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar paso')
                    ->form($this->formPaso())
                    ->mutateFormDataUsing(function (array $data) {
                        if (empty($data['orden'])) {
                            $data['orden'] = (ChatbotPaso::max('orden') ?? 0) + 1;
                        }
                        return $data;
                    })
                    ->after(fn () => cache()->forget('chatbot_pasos_activos')),
            ])
            ->emptyStateHeading('No hay pasos configurados')
            ->emptyStateDescription('Agrega pasos para definir el flujo del chatbot.');
    }

    private function formPaso(): array
    {
        return [
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('etiqueta')
                    ->label('Nombre del paso (visible solo en el panel)')
                    ->required()
                    ->maxLength(100)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('clave')
                    ->label('Clave única')
                    ->required()
                    ->maxLength(50)
                    ->helperText('Sin espacios ni caracteres especiales. Ej: nombre, correo, presupuesto'),

                Forms\Components\Select::make('tipo')
                    ->label('Tipo de paso')
                    ->required()
                    ->options([
                        'mensaje'      => 'Solo mensaje (sin esperar respuesta)',
                        'seleccion'    => 'Selección de opciones (menú)',
                        'texto_libre'  => 'Texto libre (el usuario escribe)',
                        'condicional'  => 'Condicional (aparece según paso anterior)',
                    ])
                    ->live(),

                Forms\Components\Textarea::make('mensaje')
                    ->label('Mensaje que se envía al usuario')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull()
                    ->helperText('Variables disponibles: {nombre}, {servicio}, {menu}, {total}'),

                Forms\Components\TextInput::make('siguiente_paso')
                    ->label('Clave del siguiente paso')
                    ->maxLength(50)
                    ->placeholder('Dejar vacío = crear prospecto al terminar')
                    ->helperText('Clave del paso que sigue. Vacío = fin del flujo.'),

                Forms\Components\TextInput::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->required()
                    ->default(fn () => (ChatbotPaso::max('orden') ?? 0) + 1),

                Forms\Components\Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),

                Forms\Components\Toggle::make('requerido')
                    ->label('Requerido')
                    ->helperText('Si está desactivado, el usuario puede escribir "omitir"')
                    ->default(true),
            ]),

            // Opciones del menú (solo tipo=seleccion)
            Forms\Components\Repeater::make('opciones')
                ->label('Opciones del menú')
                ->visible(fn (Get $get) => $get('tipo') === 'seleccion')
                ->schema([
                    Forms\Components\TextInput::make('valor')
                        ->label('Número/valor')
                        ->required()
                        ->maxLength(10),
                    Forms\Components\TextInput::make('etiqueta')
                        ->label('Texto de la opción')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\Toggle::make('requiere_curp')
                        ->label('Requiere CURP')
                        ->default(false),
                ])
                ->columns(3)
                ->addActionLabel('Agregar opción')
                ->columnSpanFull(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return ChatbotPaso::query()->orderBy('orden');
    }
}
