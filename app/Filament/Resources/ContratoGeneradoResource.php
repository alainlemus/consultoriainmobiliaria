<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContratoGeneradoResource\Pages;
use App\Models\ContratoGenerado;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class ContratoGeneradoResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?string $model = ContratoGenerado::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Contratos generados';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 91;
    protected static ?string $modelLabel       = 'Contrato generado';
    protected static ?string $pluralModelLabel = 'Contratos generados';

    /** Acción "Ver" reusable para pdf / ine_acreditado / ine_solidario. */
    private static function accionVer(string $campo, string $label): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make("ver_{$campo}")
            ->label($label)
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->visible(fn (ContratoGenerado $record) => (bool) $record->{"{$campo}_path"})
            ->modalHeading($label)
            ->modalWidth('4xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (ContratoGenerado $record) use ($campo, $label) {
                $url = URL::temporarySignedRoute(
                    'api.contratos_generados.descargar',
                    now()->addMinutes(30),
                    ['id' => $record->id, 'campo' => $campo]
                );

                if ($campo === 'pdf') {
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
                                    style="width:100%;height:85vh;border:none;border-radius:6px;background:#f3f4f6;">
                                <p style="text-align:center;color:#6b7280;padding:32px;font-size:14px;">
                                    Tu navegador no puede mostrar el PDF en línea.<br>
                                    <a href="' . e($url) . '" target="_blank" style="color:#2563eb;">Haz clic aquí para abrirlo</a>.
                                </p>
                            </object>
                        </div>';
                } else {
                    $html = '<img src="' . e($url) . '" alt="' . e($label) . '"
                                 style="max-width:100%;max-height:85vh;display:block;
                                        margin:0 auto;border-radius:6px;object-fit:contain;">';
                }

                return new HtmlString($html);
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Trámite')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('folio')->label('Folio')->maxLength(100),
                    Forms\Components\TextInput::make('tipo_tramite')->label('Tipo de trámite')->maxLength(150),
                    Forms\Components\TextInput::make('ciudad')->label('Ciudad')->maxLength(150),
                    Forms\Components\TextInput::make('monto_credito')->label('Monto del crédito')->numeric()->prefix('$'),
                    Forms\Components\TextInput::make('honorarios_porcentaje')->label('Honorarios (%)')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('honorarios_monto')->label('Honorarios (monto)')->numeric()->prefix('$'),
                ]),

            \Filament\Schemas\Components\Section::make('Acreditado')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('acreditado_nombre')->label('Nombre')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('acreditado_curp')->label('CURP')->maxLength(20),
                    Forms\Components\TextInput::make('acreditado_rfc')->label('RFC')->maxLength(20),
                    Forms\Components\TextInput::make('acreditado_nss')->label('NSS')->maxLength(20),
                    Forms\Components\TextInput::make('acreditado_clave_elector')->label('Clave de elector')->maxLength(20),
                    Forms\Components\Textarea::make('acreditado_domicilio')->label('Domicilio')->rows(2)->columnSpanFull(),
                ]),

            \Filament\Schemas\Components\Section::make('Obligado solidario')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('solidario_nombre')->label('Nombre')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('solidario_curp')->label('CURP')->maxLength(20),
                    Forms\Components\TextInput::make('solidario_rfc')->label('RFC')->maxLength(20),
                    Forms\Components\Textarea::make('solidario_domicilio')->label('Domicilio')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('acreditado_nombre')->label('Acreditado')->searchable(),
                Tables\Columns\TextColumn::make('solidario_nombre')->label('Obligado solidario')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('tipo_tramite')->label('Trámite')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('asesor.name')->label('Asesor')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Generado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                static::accionVer('pdf', 'Ver contrato (PDF)'),
                static::accionVer('ine_acreditado', 'Ver INE del acreditado'),
                static::accionVer('ine_solidario', 'Ver INE del solidario'),
                \Filament\Actions\EditAction::make()->label('Editar datos'),
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
            'index' => Pages\ListContratosGenerados::route('/'),
            'edit'  => Pages\EditContratoGenerado::route('/{record}/edit'),
        ];
    }
}
