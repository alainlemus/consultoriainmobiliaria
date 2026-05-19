@php
$secciones = [
    [
        'id'        => 'inicio',
        'icono'     => '🚀',
        'titulo'    => 'Primeros pasos',
        'subtitulo' => 'Cómo empezar a usar la app',
        'pasos'     => [
            ['icono'=>'📱','titulo'=>'Descarga e instala la app','desc'=>'Descarga la app <strong>Consultoría Inmobiliaria</strong> desde el enlace que te proporcionó tu administrador. Instala el archivo APK (Android) o descárgala desde App Store (iOS).'],
            ['icono'=>'🔐','titulo'=>'Inicia sesión','desc'=>'Usa el correo y contraseña que te dio el administrador. Si olvidaste tu contraseña, contacta al admin para que la restablezca desde el panel.'],
            ['icono'=>'👤','titulo'=>'Completa tu perfil','desc'=>'Ve a la pestaña <strong>Perfil</strong> y llena tu información: teléfono, banco y CLABE interbancaria. Estos datos son necesarios para recibir el pago de comisiones. Sube tu foto de perfil con la cámara frontal.'],
        ],
    ],
    [
        'id'        => 'prospectos',
        'icono'     => '🤝',
        'titulo'    => 'Registrar Prospectos',
        'subtitulo' => 'Tu pipeline de clientes',
        'pasos'     => [
            ['icono'=>'➕','titulo'=>'Agregar un prospecto','desc'=>'En la pestaña <strong>Inicio</strong> toca el botón <strong>"+ Nuevo Prospecto"</strong>. Llena nombre, teléfono, correo y el tipo de crédito que le interesa (FOVISSSTE o INFONAVIT).'],
            ['icono'=>'📊','titulo'=>'Estados del prospecto','desc'=>'Cada prospecto avanza por etapas: <strong>Nuevo → Contactado → Calificado → Convertido</strong>. Actualiza el estado cada vez que avances con el cliente.'],
            ['icono'=>'📁','titulo'=>'Convertir a Expediente','desc'=>'Cuando el prospecto esté listo para iniciar trámite, toca <strong>"Abrir Expediente"</strong> desde su tarjeta. Esto lo convierte en expediente activo y desaparece de la lista de prospectos.'],
            ['icono'=>'🧮','titulo'=>'Simular precalificación','desc'=>'Antes de registrar al prospecto puedes usar el <strong>Simulador de Precalificación</strong> en el menú. Ingresa salario, edad y tipo de crédito para calcular si califica y el monto aproximado.'],
        ],
    ],
    [
        'id'        => 'expedientes',
        'icono'     => '📁',
        'titulo'    => 'Gestionar Expedientes',
        'subtitulo' => 'El seguimiento de cada trámite',
        'pasos'     => [
            ['icono'=>'📋','titulo'=>'Lista de expedientes','desc'=>'En la pestaña <strong>Expedientes</strong> verás solo los expedientes asignados a ti. Puedes filtrar por estado y buscar por nombre del cliente.'],
            ['icono'=>'📄','titulo'=>'Subir documentos','desc'=>'Entra al expediente y toca <strong>"Documentos"</strong>. Selecciona el tipo de documento (INE, CURP, comprobante de ingresos, etc.) y toma la foto o selecciónala de tu galería. El documento se sube automáticamente.'],
            ['icono'=>'👁️','titulo'=>'Ver documentos','desc'=>'Toca cualquier documento para abrirlo directamente en tu navegador. Los PDFs se muestran en línea sin necesidad de descargarlos.'],
            ['icono'=>'🔄','titulo'=>'Estado del expediente','desc'=>'El estado lo actualiza el administrador: <strong>En proceso → Documentación → Autorizado → Escrituración → Cerrado</strong>. Cuando esté <strong>Cerrado</strong>, verás tu comisión generada.'],
        ],
    ],
    [
        'id'        => 'mapa',
        'icono'     => '🗺️',
        'titulo'    => 'Mapa de Visitas',
        'subtitulo' => 'Registra tu actividad en campo',
        'pasos'     => [
            ['icono'=>'📍','titulo'=>'Registrar una visita','desc'=>'En la pestaña <strong>Mapa</strong> toca el botón <strong>"+ Registrar Visita"</strong>. La app detecta tu ubicación automáticamente. Agrega una nota y toma fotos del lugar.'],
            ['icono'=>'🏠','titulo'=>'Tipos de visita','desc'=>'Selecciona el tipo: <strong>Cliente</strong> (visita a un prospecto o cliente) o <strong>Propiedad</strong> (visita a un inmueble en evaluación).'],
            ['icono'=>'📸','titulo'=>'Fotos en campo','desc'=>'Puedes tomar hasta varias fotos por visita. Las fotos se suben automáticamente. Si no tienes internet, se guardan y sincronizan cuando recuperes señal.'],
            ['icono'=>'📌','titulo'=>'Vincular a un prospecto','desc'=>'Al registrar la visita, puedes asociarla a un prospecto existente. Esto ayuda al admin a dar seguimiento al historial del cliente.'],
        ],
    ],
    [
        'id'        => 'comisiones',
        'icono'     => '💰',
        'titulo'    => 'Mis Comisiones',
        'subtitulo' => 'Consulta tus ingresos',
        'pasos'     => [
            ['icono'=>'💵','titulo'=>'¿Cuándo aparece una comisión?','desc'=>'Las comisiones aparecen en tu app cuando el administrador las registra, generalmente al cerrar un expediente. Verás el monto, fecha y expediente asociado.'],
            ['icono'=>'🔄','titulo'=>'Estados de tu comisión','desc'=>'<strong>Pendiente</strong> → generada, aún no pagada. <strong>Aprobada</strong> → revisada y lista para transferencia. <strong>Pagada</strong> → ya depositada en tu cuenta. Las comisiones rechazadas no aparecen en tu app.'],
            ['icono'=>'🏦','titulo'=>'¿Cómo recibo el pago?','desc'=>'El pago se hace vía transferencia a la CLABE que registraste en tu perfil. Verifica que tu CLABE esté correcta antes de que se cierre tu primer expediente.'],
            ['icono'=>'📊','titulo'=>'Resumen del mes','desc'=>'Al inicio de la pantalla de comisiones verás el resumen: total pendiente, total pagado y número de expedientes cerrados en el mes.'],
        ],
    ],
    [
        'id'        => 'offline',
        'icono'     => '📶',
        'titulo'    => 'Uso sin Internet',
        'subtitulo' => 'La app funciona offline',
        'pasos'     => [
            ['icono'=>'💾','titulo'=>'Datos en caché','desc'=>'Tu lista de prospectos y expedientes se guarda localmente. Si pierdes señal, puedes seguir consultando la información que ya cargaste.'],
            ['icono'=>'🔄','titulo'=>'Sincronización automática','desc'=>'Las visitas y fotos que registres sin internet se guardan en una cola. Cuando recuperes señal, la app las sincroniza automáticamente en segundo plano.'],
            ['icono'=>'⚠️','titulo'=>'Subir documentos','desc'=>'La subida de documentos requiere conexión. Si intentas subir sin internet, verás un mensaje de error. Espera a tener señal e inténtalo de nuevo.'],
        ],
    ],
    [
        'id'        => 'perfil',
        'icono'     => '👤',
        'titulo'    => 'Mi Perfil',
        'subtitulo' => 'Gestiona tu información personal',
        'pasos'     => [
            ['icono'=>'✏️','titulo'=>'Editar información','desc'=>'En la pestaña <strong>Perfil</strong> toca cualquier campo para editarlo directamente. Puedes cambiar tu nombre, teléfono, banco y CLABE.'],
            ['icono'=>'📸','titulo'=>'Cambiar foto de perfil','desc'=>'Toca tu foto de perfil y selecciona <strong>"Tomar selfie"</strong>. La cámara frontal se abrirá automáticamente. La foto se sube y actualiza en toda la plataforma.'],
            ['icono'=>'🔐','titulo'=>'Cambiar contraseña','desc'=>'Para cambiar tu contraseña, contacta al administrador. Por seguridad, el cambio de contraseña solo se hace desde el panel web.'],
            ['icono'=>'🚪','titulo'=>'Cerrar sesión','desc'=>'Al final de la pantalla Perfil encontrarás el botón <strong>"Cerrar sesión"</strong>. Al cerrarlo, necesitarás volver a ingresar tus credenciales.'],
        ],
    ],
];
@endphp

@foreach($secciones as $s)
<div x-data="{ abierto: false }"
     class="rounded-xl border border-white/10 bg-white/5 overflow-hidden">

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
