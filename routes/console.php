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
// Diario: cada día a las 8 AM (cubre el día anterior)
Schedule::command('reportes:enviar --tipo=diario')->dailyAt('08:00');

// Semanal: todos los lunes a las 8 AM (cubre la semana anterior lun–dom)
Schedule::command('reportes:enviar --tipo=semanal')->weeklyOn(1, '08:00');

// Mensual: el día 1 de cada mes a las 8 AM (cubre el mes anterior)
Schedule::command('reportes:enviar --tipo=mensual')->monthlyOn(1, '08:00');
