<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ApiMonitor extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Monitor API';
    protected static ?string $title           = 'Monitor de la API Móvil';
    protected static ?string $navigationGroup = 'API Móvil';
    protected static ?int    $navigationSort  = 3;
    protected static ?string $slug            = 'api-monitor';

    protected static string $view = 'filament.pages.api-monitor';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    /**
     * Tokens Sanctum activos de la app móvil, agrupados por usuario.
     */
    public function getTokensActivos(): \Illuminate\Support\Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
            return collect();
        }

        return DB::table('personal_access_tokens as t')
            ->join('users as u', 'u.id', '=', 't.tokenable_id')
            ->where('t.tokenable_type', 'App\\Models\\User')
            ->where(function ($q) {
                $q->whereNull('t.expires_at')
                  ->orWhere('t.expires_at', '>', now());
            })
            ->orderByDesc('t.last_used_at')
            ->select([
                't.id',
                't.name as token_name',
                'u.name as usuario',
                'u.email',
                't.created_at',
                't.last_used_at',
                't.expires_at',
            ])
            ->get();
    }

    /**
     * Resumen de tokens: total, activos últimas 24h, expirados.
     */
    public function getResumenTokens(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
            return ['total' => 0, 'activos' => 0, 'expirados' => 0];
        }

        $total   = DB::table('personal_access_tokens')->count();
        $activos = DB::table('personal_access_tokens')
            ->where('last_used_at', '>=', now()->subDay())
            ->count();
        $expirados = DB::table('personal_access_tokens')
            ->where('expires_at', '<', now())
            ->count();

        return compact('total', 'activos', 'expirados');
    }

    /**
     * Dispositivos FCM registrados (tabla device_tokens si existe, sino placeholder).
     */
    public function getDispositivosRegistrados(): \Illuminate\Support\Collection
    {
        // La tabla se creará cuando se implemente la API
        // Retorna colección vacía por ahora para que la vista no falle
        if (! \Illuminate\Support\Facades\Schema::hasTable('device_tokens')) {
            return collect();
        }

        return DB::table('device_tokens as d')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->orderByDesc('d.updated_at')
            ->select(['u.name', 'u.email', 'd.plataforma', 'd.updated_at'])
            ->get();
    }

    /**
     * Operaciones de sync pendientes (tabla sync_queue si existe).
     */
    public function getSyncPendientes(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sync_queue')) {
            return ['total' => 0, 'errores' => 0, 'ultima_sync' => null];
        }

        return [
            'total'      => DB::table('sync_queue')->where('estado', 'pendiente')->count(),
            'errores'    => DB::table('sync_queue')->where('estado', 'error')->count(),
            'ultima_sync'=> DB::table('sync_queue')->max('procesado_en'),
        ];
    }

    /**
     * Estadísticas rápidas para las tarjetas del dashboard.
     */
    public function getStats(): array
    {
        $resumen     = $this->getResumenTokens();
        $dispositivos = $this->getDispositivosRegistrados();
        $sync        = $this->getSyncPendientes();

        return [
            [
                'label'       => 'Tokens activos',
                'valor'       => $resumen['total'],
                'descripcion' => $resumen['activos'] . ' usados en las últimas 24h',
                'color'       => 'success',
                'icono'       => 'heroicon-o-key',
            ],
            [
                'label'       => 'Tokens expirados',
                'valor'       => $resumen['expirados'],
                'descripcion' => 'Pendientes de limpieza',
                'color'       => $resumen['expirados'] > 0 ? 'warning' : 'gray',
                'icono'       => 'heroicon-o-x-circle',
            ],
            [
                'label'       => 'Dispositivos registrados',
                'valor'       => $dispositivos->count(),
                'descripcion' => 'Con token FCM activo',
                'color'       => 'info',
                'icono'       => 'heroicon-o-device-phone-mobile',
            ],
            [
                'label'       => 'Sync pendientes',
                'valor'       => $sync['total'],
                'descripcion' => $sync['errores'] . ' con error',
                'color'       => $sync['errores'] > 0 ? 'danger' : 'gray',
                'icono'       => 'heroicon-o-arrow-path',
            ],
        ];
    }
}
