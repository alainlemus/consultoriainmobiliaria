<?php

namespace App\Filament\Resources\Acreditados;

use App\Filament\Resources\Acreditados\Pages\ListAcreditados;
use App\Filament\Resources\Acreditados\Pages\EditAcreditado;
use App\Filament\Resources\ExpedienteResource;
use App\Models\Acreditado;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class AcreditadoResource extends Resource
{
    protected static ?string $model = Acreditado::class;

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Acreditados App';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $modelLabel      = 'Acreditado';
    protected static ?string $pluralModelLabel = 'Acreditados App';

    // Solo super_admin puede gestionar acreditados
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool { return false; } // se crean desde la app

    // ── Formulario (solo lectura + edición de estado) ─────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Información del acreditado')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre completo')
                        ->disabled(),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->disabled(),
                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->disabled(),
                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')
                        ->disabled(),
                    Forms\Components\TextInput::make('nss')
                        ->label('NSS')
                        ->disabled(),
                    Forms\Components\TextInput::make('rfc')
                        ->label('RFC')
                        ->disabled(),
                ]),

            \Filament\Schemas\Components\Section::make('Estado de la cuenta')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('activo')
                        ->label('Cuenta activa')
                        ->helperText('Desactivar impide que el acreditado pueda iniciar sesión en la app.'),
                    Forms\Components\Placeholder::make('curp_verified_at')
                        ->label('CURP verificado')
                        ->content(fn ($record) => $record?->curp_verified_at
                            ? '✅ Vinculado el ' . $record->curp_verified_at->format('d/m/Y H:i')
                            : '⏳ Sin vincular a expediente'
                        ),
                    Forms\Components\Placeholder::make('created_at')
                        ->label('Fecha de registro')
                        ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i') ?? '—'),
                    Forms\Components\Placeholder::make('tokens_count')
                        ->label('Sesiones activas')
                        ->content(fn ($record) => $record?->tokens()->count() . ' dispositivo(s)'),
                ]),

            \Filament\Schemas\Components\Section::make('Expediente vinculado')
                ->schema([
                    Forms\Components\Placeholder::make('expediente_info')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function ($record) {
                            $exp = $record?->expedientes()->with('etapa','tipoTramite')->latest()->first();
                            if (! $exp) {
                                return new \Illuminate\Support\HtmlString(
                                    '<p style="color:#6b7280;font-size:13px;">Este acreditado no tiene expediente vinculado aún.</p>'
                                );
                            }
                            $url    = ExpedienteResource::getUrl('edit', ['record' => $exp]);
                            $etapa  = $exp->etapa?->nombre ?? 'Sin etapa';
                            $tipo   = $exp->tipoTramite?->nombre ?? '—';
                            $estado = match($exp->estado) {
                                'en_proceso' => '🔵 En proceso',
                                'cerrado'    => '✅ Cerrado',
                                'cancelado'  => '❌ Cancelado',
                                default      => ucfirst($exp->estado),
                            };
                            return new \Illuminate\Support\HtmlString(
                                '<div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">'
                                . '<div>'
                                . '<p style="font-size:14px;font-weight:700;color:#f9fafb;">' . e($exp->folio) . '</p>'
                                . '<p style="font-size:12px;color:#9ca3af;">' . e($tipo) . ' · ' . e($etapa) . '</p>'
                                . '<p style="font-size:12px;color:#d97706;">' . $estado . '</p>'
                                . '</div>'
                                . '<a href="' . e($url) . '" style="background:#1d4ed8;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;">Ver expediente →</a>'
                                . '</div>'
                            );
                        }),
                ]),
        ]);
    }

    // ── Tabla ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto_perfil')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn () => null)
                    ->getStateUsing(fn ($record) => $record->foto_perfil
                        ? URL::signedRoute('api.acreditado.foto-perfil', ['acreditado' => $record->id], now()->addHour())
                        : null
                    )
                    ->width(36)
                    ->height(36),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('curp')
                    ->label('CURP')
                    ->placeholder('—')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('curp_verified_at')
                    ->label('Vinculado')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->curp_verified_at)
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->curp_verified_at
                        ? 'Expediente vinculado el ' . $record->curp_verified_at->format('d/m/Y')
                        : 'Sin expediente vinculado'
                    )
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('expedientes_count')
                    ->label('Expediente')
                    ->counts('expedientes')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state . ' activo' : 'Sin trámite')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->activo ? 'Cuenta activa' : 'Cuenta desactivada')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registro')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo desactivados'),

                Tables\Filters\TernaryFilter::make('curp_verified_at')
                    ->label('Vinculación')
                    ->trueLabel('Con expediente')
                    ->falseLabel('Sin expediente')
                    ->queries(
                        true:  fn (Builder $q) => $q->whereNotNull('curp_verified_at'),
                        false: fn (Builder $q) => $q->whereNull('curp_verified_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ver')->icon('heroicon-o-eye'),
                Tables\Actions\Action::make('toggle_activo')
                    ->label(fn ($record) => $record->activo ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->activo ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->activo ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->activo ? 'Desactivar cuenta' : 'Activar cuenta')
                    ->modalDescription(fn ($record) => $record->activo
                        ? 'El acreditado no podrá iniciar sesión en la app. ¿Continuar?'
                        : '¿Reactivar la cuenta de este acreditado?'
                    )
                    ->action(function ($record) {
                        if ($record->activo) {
                            $record->update(['activo' => false]);
                            $record->tokens()->delete(); // cerrar sesiones activas
                        } else {
                            $record->update(['activo' => true]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('desactivar')
                    ->label('Desactivar seleccionados')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(function ($r) {
                        $r->update(['activo' => false]);
                        $r->tokens()->delete();
                    })),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcreditados::route('/'),
            'edit'  => EditAcreditado::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Acreditado::where('activo', true)->count();
        return $count > 0 ? (string) $count : null;
    }
}
