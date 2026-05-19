@php
$secciones = [
    [
        'id'        => 'dashboard',
        'icono'     => '📊',
        'titulo'    => 'Dashboard y KPIs',
        'subtitulo' => 'Visión global del negocio',
        'pasos'     => [
            ['icono'=>'📈','titulo'=>'Indicadores principales','desc'=>'Al ingresar verás 4 tarjetas: <strong>Expedientes activos</strong>, <strong>Prospectos nuevos</strong>, <strong>Comisiones pendientes</strong> y <strong>Total recaudado</strong>. Estos números se actualizan en tiempo real.'],
            ['icono'=>'🏆','titulo'=>'Tabla de asesores','desc'=>'La sección "Rendimiento de Asesores" muestra un ranking con el número de expedientes y comisiones de cada asesor. Úsala para identificar a los más productivos.'],
            ['icono'=>'📅','titulo'=>'Expedientes sin movimiento','desc'=>'El widget inferior alerta sobre expedientes que llevan más de 30 días sin actualización. Dales seguimiento para no perder trámites.'],
        ],
    ],
    [
        'id'        => 'usuarios',
        'icono'     => '👥',
        'titulo'    => 'Gestión de Usuarios',
        'subtitulo' => 'Crear y administrar asesores',
        'pasos'     => [
            ['icono'=>'➕','titulo'=>'Crear un asesor','desc'=>'Ve a <strong>Usuarios → Nuevo usuario</strong>. Llena nombre, correo y contraseña. En el campo "Rol" selecciona <code>asesor</code>. Guarda — el asesor ya puede iniciar sesión en la app móvil y en el panel web.'],
            ['icono'=>'🔑','titulo'=>'Cambiar contraseña','desc'=>'Edita el usuario y escribe la nueva contraseña en el campo correspondiente. Si lo dejas vacío, la contraseña no cambia.'],
            ['icono'=>'📸','titulo'=>'Foto de perfil','desc'=>'Desde el panel puedes ver la foto de perfil que el asesor subió desde la app. No se puede editar desde el admin — el asesor la gestiona desde su app.'],
            ['icono'=>'🏦','titulo'=>'Datos bancarios','desc'=>'Los campos <strong>Banco</strong> y <strong>CLABE</strong> los llena el asesor desde su app en la sección Perfil. Los puedes consultar aquí para el pago de comisiones.'],
        ],
    ],
    [
        'id'        => 'prospectos',
        'icono'     => '🤝',
        'titulo'    => 'Prospectos (Contactos)',
        'subtitulo' => 'Gestión del pipeline de clientes',
        'pasos'     => [
            ['icono'=>'📋','titulo'=>'¿Qué es un prospecto?','desc'=>'Es una persona interesada en un crédito o servicio. Los asesores los registran desde la app móvil. En el admin puedes ver <strong>todos</strong> los prospectos de todos los asesores.'],
            ['icono'=>'🔍','titulo'=>'Filtros disponibles','desc'=>'Puedes filtrar por <strong>asesor</strong>, <strong>origen</strong> (web, app, referido) y <strong>fecha de registro</strong>. El buscador funciona por nombre, email y teléfono.'],
            ['icono'=>'🔄','titulo'=>'Estados del prospecto','desc'=>'<strong>Nuevo</strong> → <strong>Contactado</strong> → <strong>Calificado</strong> → <strong>Convertido</strong> (cuando se abre expediente) o <strong>Descartado</strong>. Los prospectos convertidos desaparecen de la lista principal.'],
            ['icono'=>'🗑️','titulo'=>'Eliminar prospectos','desc'=>'Solo el administrador puede eliminar prospectos. Los asesores no tienen este permiso desde la app.'],
        ],
    ],
    [
        'id'        => 'expedientes',
        'icono'     => '📁',
        'titulo'    => 'Expedientes',
        'subtitulo' => 'El corazón de la operación',
        'pasos'     => [
            ['icono'=>'🆔','titulo'=>'Folio automático','desc'=>'Al crear un expediente se genera automáticamente el folio con formato <code>EXP-2026-0001</code>. No es necesario asignarlo manualmente.'],
            ['icono'=>'📊','titulo'=>'Estados del expediente','desc'=>'<strong>En proceso → Documentación → Autorizado → Escrituración → Cerrado</strong>. Solo cuando está <strong>Cerrado</strong> se activan las comisiones para el asesor.'],
            ['icono'=>'📄','titulo'=>'Documentos','desc'=>'Cada expediente tiene su gestor de documentos. Puedes subir y ver documentos directamente desde el panel. Los documentos subidos por la app también aparecen aquí.'],
            ['icono'=>'💰','titulo'=>'Honorarios','desc'=>'En la pestaña "Trámite" configura el porcentaje y monto de honorarios. Estos datos se usan para calcular las comisiones del asesor.'],
            ['icono'=>'📑','titulo'=>'Contratos','desc'=>'Desde el botón "Contratos" puedes generar PDF de: Contrato de Servicios, Convenio de Honorarios y Carta Mandato. Se llenan automáticamente con los datos del expediente.'],
        ],
    ],
    [
        'id'        => 'comisiones',
        'icono'     => '💰',
        'titulo'    => 'Comisiones',
        'subtitulo' => 'Control de pagos a asesores',
        'pasos'     => [
            ['icono'=>'⚙️','titulo'=>'¿Cuándo se genera una comisión?','desc'=>'Las comisiones se crean manualmente desde el panel al cerrar un expediente. Ve a <strong>Comisiones → Nueva comisión</strong> y asocia el expediente.'],
            ['icono'=>'🔄','titulo'=>'Estados de comisión','desc'=>'<strong>Pendiente</strong> → el asesor la puede ver en su app. <strong>Aprobada</strong> → lista para pagar. <strong>Pagada</strong> → el asesor la ve en su historial. <strong>Rechazada</strong> → no aparece en la app del asesor.'],
            ['icono'=>'💳','titulo'=>'Datos de pago','desc'=>'Consulta el Banco y CLABE del asesor en su perfil antes de realizar la transferencia. Después marca la comisión como <strong>Pagada</strong>.'],
        ],
    ],
    [
        'id'        => 'mapa',
        'icono'     => '🗺️',
        'titulo'    => 'Mapa de Visitas',
        'subtitulo' => 'Seguimiento de actividad en campo',
        'pasos'     => [
            ['icono'=>'📍','titulo'=>'Ver visitas de todos los asesores','desc'=>'Como admin ves en el mapa <strong>todas las visitas</strong> de todos los asesores con sus fotos. Puedes filtrar por asesor y tipo de visita.'],
            ['icono'=>'🏠','titulo'=>'Tipos de marcadores','desc'=>'<strong>🏠 Clientes</strong> (dorado) — visita a un cliente o prospecto. <strong>🏢 Propiedades</strong> (oscuro) — visita a una propiedad en evaluación.'],
            ['icono'=>'📸','titulo'=>'Fotos de campo','desc'=>'Al tocar un marcador se despliega el detalle con las fotos que el asesor tomó durante la visita.'],
        ],
    ],
    [
        'id'        => 'api',
        'icono'     => '📡',
        'titulo'    => 'API Móvil',
        'subtitulo' => 'Gestión técnica de la app',
        'pasos'     => [
            ['icono'=>'📊','titulo'=>'Monitor de API','desc'=>'En <strong>API Móvil → Monitor</strong> puedes ver las últimas llamadas a la API, tokens activos y dispositivos registrados para push notifications.'],
            ['icono'=>'📖','titulo'=>'Documentación','desc'=>'En <strong>API Móvil → Documentación</strong> están todos los endpoints disponibles con ejemplos de request y response.'],
            ['icono'=>'⚙️','titulo'=>'Configuración','desc'=>'En <strong>API Móvil → Configuración</strong> puedes ajustar parámetros de la API como límites de rate y versión activa.'],
        ],
    ],
];
@endphp

@foreach($secciones as $s)
<div x-data="{ abierto: false }"
     class="rounded-xl border border-white/10 bg-white/5 overflow-hidden">

    {{-- Header colapsable --}}
    <button @click="abierto = !abierto"
            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-white/5 transition-colors">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ $s['icono'] }}</span>
            <div>
                <p class="font-semibold text-white text-sm">{{ $s['titulo'] }}</p>
                <p class="text-xs text-gray-500">{{ $s['subtitulo'] }}</p>
            </div>
        </div>
        <svg :class="abierto ? 'rotate-180' : ''" class="w-5 h-5 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Contenido --}}
    <div x-show="abierto" x-transition class="border-t border-white/10 px-5 py-4 space-y-4">
        @foreach($s['pasos'] as $i => $paso)
        <div class="flex gap-4">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center text-base">
                {{ $paso['icono'] }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">Paso {{ $i + 1 }}</span>
                    <p class="text-sm font-semibold text-white">{{ $paso['titulo'] }}</p>
                </div>
                <p class="text-sm text-gray-400 leading-relaxed">{!! $paso['desc'] !!}</p>
            </div>
        </div>
        @if(!$loop->last)
        <div class="ml-4 border-l border-white/10 pl-8 -mt-2 -mb-2 h-4"></div>
        @endif
        @endforeach
    </div>
</div>
@endforeach
