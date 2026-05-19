@component('mail::message')
# Reporte {{ ucfirst($tipo) }} de Gestión

**Período:** {{ $periodo }}

Adjunto encontrarás el reporte completo en PDF con el resumen de actividad del período.

---

## Resumen rápido

@component('mail::table')
| Métrica | Valor |
|:---|---:|
| Expedientes abiertos en el período | {{ $datos['expedientes']['abiertos_periodo'] }} |
| Expedientes cerrados en el período | {{ $datos['expedientes']['cerrados_periodo'] }} |
| Expedientes activos (total) | {{ $datos['expedientes']['activos_total'] }} |
| Prospectos nuevos | {{ $datos['prospectos']['nuevos_periodo'] }} |
| Prospectos convertidos | {{ $datos['prospectos']['convertidos_periodo'] }} |
| Prospectos pendientes de cierre | {{ $datos['prospectos']['pendientes_cierre'] }} |
| Comisiones generadas | $ {{ number_format($datos['comisiones']['generadas_monto'], 2) }} |
| Comisiones pagadas | $ {{ number_format($datos['comisiones']['pagadas_monto'], 2) }} |
| Comisiones pendientes de pago | $ {{ number_format($datos['comisiones']['pendientes_monto'], 2) }} |
| Seguimientos registrados | {{ $datos['seguimientos']['total_periodo'] }} |
| Expedientes sin movimiento (+{{ $datos['sin_movimiento']['umbral_dias'] }} días) | {{ $datos['sin_movimiento']['total'] }} |
| Expedientes con documentos pendientes | {{ $datos['documentos']['expedientes_con_pendientes'] }} |
@endcomponent

---

@if($datos['sin_movimiento']['total'] > 0)
> ⚠️ **Atención:** Hay **{{ $datos['sin_movimiento']['total'] }}** expediente(s) sin movimiento en los últimos {{ $datos['sin_movimiento']['umbral_dias'] }} días. Consulta el PDF para el detalle.
@else
> ✅ Todos los expedientes activos tienen seguimiento reciente.
@endif

@component('mail::button', ['url' => config('app.url') . '/admin', 'color' => 'green'])
Ver panel de administración
@endcomponent

_El reporte completo con todas las secciones y detalles se encuentra adjunto en PDF._

Saludos,
**{{ config('app.name') }}**

@endcomponent
