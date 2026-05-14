<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Categoria;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Blog';
    protected static ?string $navigationGroup = 'Configuración del sitio';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Artículo';
    protected static ?string $pluralModelLabel = 'Artículos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del artículo')->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                        $set('slug', Str::slug($state))
                    ),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->disabled()
                    ->dehydrated()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Se genera automáticamente a partir del título.'),

                Forms\Components\Select::make('categoria')
                    ->label('Categoría')
                    ->options(fn () => Categoria::orderBy('nombre')->pluck('nombre', 'nombre'))
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nueva categoría')
                            ->required()
                            ->unique('categorias', 'nombre')
                            ->maxLength(100),
                    ])
                    ->createOptionUsing(function (array $data): string {
                        Categoria::create(['nombre' => $data['nombre']]);
                        return $data['nombre'];
                    })
                    ->createOptionModalHeading('Crear nueva categoría'),

                Forms\Components\FileUpload::make('imagen')
                    ->label('Imagen destacada')
                    ->image()
                    ->directory('posts')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('resumen')
                    ->label('Resumen (meta descripción)')
                    ->rows(2)
                    ->maxLength(300)
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('contenido')
                    ->label('Contenido')
                    ->required()
                    ->fileAttachmentsDirectory('posts/attachments')
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Publicación')->schema([
                Forms\Components\Select::make('estado')
                    ->label('Estado')
                    ->options([
                        Post::ESTADO_BORRADOR   => '📝 Borrador',
                        Post::ESTADO_PROGRAMADO => '🕐 Programado',
                        Post::ESTADO_PUBLICADO  => '✅ Publicado',
                    ])
                    ->default(Post::ESTADO_BORRADOR)
                    ->required()
                    ->live()
                    ->helperText(fn ($state) => match ($state) {
                        Post::ESTADO_BORRADOR   => 'El artículo no será visible en el sitio.',
                        Post::ESTADO_PROGRAMADO => 'Se publicará automáticamente en la fecha indicada.',
                        Post::ESTADO_PUBLICADO  => 'El artículo es visible en el sitio ahora.',
                        default                 => '',
                    }),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Fecha de publicación')
                    ->helperText('Requerida para artículos programados.')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->visible(fn (Forms\Get $get) => $get('estado') !== Post::ESTADO_BORRADOR),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->circular(false)
                    ->width(60)
                    ->height(40),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('categoria')
                    ->label('Categoría')
                    ->colors(['primary']),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => Post::ESTADO_PUBLICADO,
                        'warning' => Post::ESTADO_PROGRAMADO,
                        'gray'    => Post::ESTADO_BORRADOR,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Post::ESTADO_PUBLICADO  => 'Publicado',
                        Post::ESTADO_PROGRAMADO => 'Programado',
                        default                 => 'Borrador',
                    }),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Fecha programada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options(fn () => Categoria::orderBy('nombre')->pluck('nombre', 'nombre')),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        Post::ESTADO_BORRADOR   => 'Borrador',
                        Post::ESTADO_PROGRAMADO => 'Programado',
                        Post::ESTADO_PUBLICADO  => 'Publicado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
