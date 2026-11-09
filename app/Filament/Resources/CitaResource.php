<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CitaResource\Pages;
use App\Models\Cita;
use App\Models\Cobertura;
use App\Services\CitaService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** Agenda de citas: el super admin confirma, reagenda, cancela y marca asistencia. El asesor ve las suyas. */
class CitaResource extends Resource
{
    protected static ?string $model = Cita::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Citas';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Cita';
    protected static ?string $pluralModelLabel = 'Citas';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'asesor']);
    }

    public static function canCreate(): bool { return false; }   // se agendan desde la app
    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()->with(['cobertura', 'asesor']);
        if (Auth::user()?->hasRole('asesor') && ! Auth::user()->hasRole('super_admin')) {
            $q->where('asesor_id', Auth::id());
        }
        return $q;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::user()?->hasRole('super_admin')) return null;
        $n = Cita::where('estado', Cita::SOLICITADA)->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Cita')->schema([
                Forms\Components\TextInput::make('nombre')->label('Cliente')->disabled(),
                Forms\Components\TextInput::make('telefono')->label('Teléfono')->disabled(),
                Forms\Components\TextInput::make('estado')->label('Estado')->formatStateUsing(fn ($state) => Cita::ESTADOS[$state] ?? $state)->disabled(),
                Forms\Components\TextInput::make('tipo')->label('Tipo')->formatStateUsing(fn ($state) => $state === 'domicilio' ? 'Visita a domicilio' : 'En oficina')->disabled(),
                Forms\Components\DateTimePicker::make('inicia_at')->label('Inicia')->timezone(CitaService::TZ)->disabled(),
                Forms\Components\DateTimePicker::make('termina_at')->label('Termina')->timezone(CitaService::TZ)->disabled(),
                Forms\Components\TextInput::make('cobertura.nombre')->label('Oficina')->formatStateUsing(fn ($state, $record) => $record?->cobertura?->nombre)->disabled(),
                Forms\Components\TextInput::make('direccion')->label('Dirección (domicilio)')->disabled(),
                Forms\Components\TextInput::make('asesor.name')->label('Asesor')->formatStateUsing(fn ($state, $record) => $record?->asesor?->name)->disabled(),
                Forms\Components\Textarea::make('notas')->label('Notas')->rows(3)->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('inicia_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('inicia_at')->label('Cuándo')->dateTime('D j M · g:i a', CitaService::TZ)->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('nombre')->label('Cliente')->searchable()->description(fn (Cita $record) => $record->telefono),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()->formatStateUsing(fn ($s) => $s === 'domicilio' ? 'Domicilio' : 'Oficina')->color(fn ($s) => $s === 'domicilio' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('lugar')->label('Lugar')->getStateUsing(fn (Cita $record) => $record->lugar())->limit(45)->wrap(),
                Tables\Columns\TextColumn::make('asesor.name')->label('Asesor')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn ($s) => Cita::ESTADOS[$s] ?? $s)
                    ->color(fn ($s) => match ($s) { 'solicitada' => 'warning', 'confirmada' => 'success', 'cancelada' => 'danger', 'realizada' => 'gray', 'no_asistio' => 'danger', default => 'gray' }),
                Tables\Columns\TextColumn::make('creada_por_tipo')->label('La pidió')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->label('Estado')->options(Cita::ESTADOS),
                Tables\Filters\SelectFilter::make('cobertura_id')->label('Oficina')->options(Cobertura::conCitas()->pluck('nombre', 'id')),
                Tables\Filters\Filter::make('proximas')->label('Solo próximas')->default()->query(fn (Builder $q) => $q->where('inicia_at', '>=', now()->startOfDay())),
            ])
            ->actions([
                \Filament\Actions\Action::make('confirmar')->label('Confirmar')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Cita $record) => Auth::user()?->hasRole('super_admin') && $record->estado === Cita::SOLICITADA)
                    ->requiresConfirmation()
                    ->action(fn (Cita $record) => app(CitaService::class)->confirmar($record, Auth::user())),
                \Filament\Actions\Action::make('reagendar')->label('Reagendar')->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn (Cita $record) => Auth::user()?->hasRole('super_admin') && $record->estaActiva())
                    ->form([Forms\Components\DateTimePicker::make('inicia_at')->label('Nueva fecha y hora')->timezone(CitaService::TZ)->seconds(false)->required()])
                    ->action(fn (Cita $record, array $data) => app(CitaService::class)->reagendar($record, $data['inicia_at'], Auth::user())),
                \Filament\Actions\Action::make('cancelar')->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn (Cita $record) => Auth::user()?->hasRole('super_admin') && $record->estaActiva())
                    ->form([Forms\Components\TextInput::make('motivo')->label('Motivo (le llega al cliente)')->maxLength(255)])
                    ->action(fn (Cita $record, array $data) => app(CitaService::class)->cancelar($record, $data['motivo'] ?? null, Auth::user())),
                \Filament\Actions\Action::make('asistio')->label('Asistió')->icon('heroicon-o-hand-thumb-up')->color('gray')
                    ->visible(fn (Cita $record) => Auth::user()?->hasRole('super_admin') && $record->estado === Cita::CONFIRMADA && $record->inicia_at->isPast())
                    ->action(fn (Cita $record) => app(CitaService::class)->marcar($record, true)),
                \Filament\Actions\Action::make('no_asistio')->label('No asistió')->icon('heroicon-o-hand-thumb-down')->color('gray')
                    ->visible(fn (Cita $record) => Auth::user()?->hasRole('super_admin') && $record->estado === Cita::CONFIRMADA && $record->inicia_at->isPast())
                    ->action(fn (Cita $record) => app(CitaService::class)->marcar($record, false)),
                \Filament\Actions\EditAction::make()->label('Ver'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCitas::route('/'),
            'edit'  => Pages\EditCita::route('/{record}/edit'),
        ];
    }
}
