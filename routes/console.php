<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publica automáticamente los artículos cuya fecha programada ya llegó
Schedule::command('posts:publicar-programados')->everyMinute();

// Notifica a asesores sobre expedientes sin movimiento en los últimos 7 días
// Se ejecuta cada mañana a las 8 AM
Schedule::command('expedientes:alertar-sin-movimiento')->dailyAt('08:00');

// ── Reportes de gestión para super_admin ──────────────────────────────────
// Diario: lunes a viernes a las 8 PM (cubre el día actual)
Schedule::command('reportes:enviar --tipo=diario')
    ->weekdays()
    ->dailyAt('20:00');

// Semanal: sábados a las 8 AM (cubre la semana lun–vie anterior)
Schedule::command('reportes:enviar --tipo=semanal')
    ->weeklyOn(6, '08:00');

// Mensual: último día del mes a las 8 PM (cubre el mes actual)
Schedule::command('reportes:enviar --tipo=mensual')
    ->lastDayOfMonth('20:00');
