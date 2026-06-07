<?php

namespace App\Filament\Pages;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Schema;
use Filament\Pages\Page;

class AyudaTutorial extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel = 'Ayuda y Tutorial';
    protected static ?string $title           = 'Ayuda y Tutorial';
    protected static ?int    $navigationSort  = 99;
    protected static ?string $slug            = 'ayuda';
    protected string $view = 'filament.pages.ayuda-tutorial';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getRol(): string
    {
        return auth()->user()?->hasRole('super_admin') ? 'super_admin' : 'asesor';
    }

    // ── Infolist Admin ────────────────────────────────────────────────────────

    public function adminInfolist(Schema $schema): Schema
    {
        return $schema
            ->state([])
            ->schema([

                Section::make('Dashboard y KPIs')
                    ->description('Visión global del negocio')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('kpi1')
                            ->label('Indicadores principales')
                            ->icon('heroicon-o-arrow-trending-up')
                            ->state('Al ingresar verás 4 tarjetas: Expedientes activos, Prospectos nuevos, Comisiones pendientes y Total recaudado. Estos números se actualizan en tiempo real.'),
                        TextEntry::make('kpi2')
                            ->label('Tabla de asesores')
                            ->icon('heroicon-o-trophy')
                            ->state('La sección "Rendimiento de Asesores" muestra un ranking con expedientes y comisiones de cada asesor. Úsala para identificar a los más productivos.'),
                        TextEntry::make('kpi3')
                            ->label('Expedientes sin movimiento')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->columnSpanFull()
                            ->state('El widget inferior alerta sobre expedientes con más de 30 días sin actualización. Dales seguimiento para no perder trámites.'),
                    ]),

                Section::make('Gestión de Usuarios')
                    ->description('Crear y administrar asesores')
                    ->icon('heroicon-o-users')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('u1')
                            ->label('Crear un asesor')
                            ->icon('heroicon-o-user-plus')
                            ->state('Ve a Usuarios → Nuevo usuario. Llena nombre, correo y contraseña. En el campo "Rol" selecciona asesor. El asesor ya puede iniciar sesión en la app y en el panel web.'),
                        TextEntry::make('u2')
                            ->label('Cambiar contraseña')
                            ->icon('heroicon-o-key')
                            ->state('Edita el usuario y escribe la nueva contraseña. Si lo dejas vacío, la contraseña no cambia.'),
                        TextEntry::make('u3')
                            ->label('Foto de perfil')
                            ->icon('heroicon-o-camera')
                            ->state('Puedes ver la foto de perfil que el asesor subió desde la app. El asesor la gestiona desde su app móvil en la sección Perfil.'),
                        TextEntry::make('u4')
                            ->label('Datos bancarios')
                            ->icon('heroicon-o-building-library')
                            ->state('Los campos Banco y CLABE los llena el asesor desde su app. Los puedes consultar aquí para el pago de comisiones.'),
                    ]),

                Section::make('Prospectos')
                    ->description('Gestión del pipeline de clientes')
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('p1')
                            ->label('¿Qué es un prospecto?')
                            ->icon('heroicon-o-information-circle')
                            ->state('Es una persona interesada en un crédito. Los asesores los registran desde la app. En el admin ves todos los prospectos de todos los asesores.'),
                        TextEntry::make('p2')
                            ->label('Filtros disponibles')
                            ->icon('heroicon-o-funnel')
                            ->state('Puedes filtrar por asesor, origen (web, app, referido) y fecha de registro. El buscador funciona por nombre, email y teléfono.'),
                        TextEntry::make('p3')
                            ->label('Estados del prospecto')
                            ->icon('heroicon-o-arrow-path')
                            ->state('Nuevo → Contactado → Calificado → Convertido (al abrir expediente) o Descartado. Los prospectos convertidos desaparecen de la lista principal.'),
                        TextEntry::make('p4')
                            ->label('Eliminar prospectos')
                            ->icon('heroicon-o-trash')
                            ->state('Solo el administrador puede eliminar prospectos. Los asesores no tienen este permiso desde la app.'),
                    ]),

                Section::make('Expedientes')
                    ->description('El corazón de la operación')
                    ->icon('heroicon-o-folder-open')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('e1')
                            ->label('Folio automático')
                            ->icon('heroicon-o-hashtag')
                            ->state('Al crear un expediente se genera automáticamente el folio con formato EXP-2026-0001. No es necesario asignarlo manualmente.'),
                        TextEntry::make('e2')
                            ->label('Estados del expediente')
                            ->icon('heroicon-o-arrow-path')
                            ->state('En proceso → Documentación → Autorizado → Escrituración → Cerrado. Solo cuando está Cerrado se activan las comisiones para el asesor.'),
                        TextEntry::make('e3')
                            ->label('Documentos')
                            ->icon('heroicon-o-paper-clip')
                            ->state('Cada expediente tiene su gestor de documentos. Puedes subir y ver documentos desde el panel. Los documentos subidos por la app también aparecen aquí.'),
                        TextEntry::make('e4')
                            ->label('Honorarios')
                            ->icon('heroicon-o-currency-dollar')
                            ->state('En la pestaña "Trámite" configura el porcentaje y monto de honorarios. Estos datos se usan para calcular las comisiones del asesor.'),
                        TextEntry::make('e5')
                            ->label('Contratos')
                            ->icon('heroicon-o-document-text')
                            ->columnSpanFull()
                            ->state('Desde "Contratos" puedes generar PDF de: Contrato de Servicios, Convenio de Honorarios y Carta Mandato. Se llenan automáticamente con los datos del expediente.'),
                    ]),

                Section::make('Comisiones')
                    ->description('Control de pagos a asesores')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('c1')
                            ->label('¿Cuándo se genera una comisión?')
                            ->icon('heroicon-o-plus-circle')
                            ->state('Las comisiones se crean manualmente desde el panel al cerrar un expediente. Ve a Comisiones → Nueva comisión y asocia el expediente.'),
                        TextEntry::make('c2')
                            ->label('Estados de comisión')
                            ->icon('heroicon-o-arrow-path')
                            ->state('Pendiente → el asesor la ve en su app. Aprobada → lista para pagar. Pagada → el asesor la ve en su historial. Rechazada → no aparece en la app.'),
                        TextEntry::make('c3')
                            ->label('Datos de pago')
                            ->icon('heroicon-o-credit-card')
                            ->columnSpanFull()
                            ->state('Consulta el Banco y CLABE del asesor en su perfil antes de transferir. Después marca la comisión como Pagada.'),
                    ]),

                Section::make('Mapa de Visitas')
                    ->description('Seguimiento de actividad en campo')
                    ->icon('heroicon-o-map-pin')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('m1')
                            ->label('Ver visitas de todos los asesores')
                            ->icon('heroicon-o-eye')
                            ->state('Como admin ves todas las visitas de todos los asesores con sus fotos. Puedes filtrar por asesor y tipo de visita.'),
                        TextEntry::make('m2')
                            ->label('Tipos de marcadores')
                            ->icon('heroicon-o-map')
                            ->state('Clientes (dorado) — visita a un cliente o prospecto. Propiedades (oscuro) — visita a una propiedad en evaluación.'),
                        TextEntry::make('m3')
                            ->label('Fotos de campo')
                            ->icon('heroicon-o-camera')
                            ->columnSpanFull()
                            ->state('Al tocar un marcador se despliega el detalle con las fotos que el asesor tomó durante la visita.'),
                    ]),

                Section::make('WhatsApp — Configuración')
                    ->description('Conectar y gestionar la sesión de WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('wa1')
                            ->label('Conectar WhatsApp por primera vez')
                            ->icon('heroicon-o-qr-code')
                            ->state('Ve a Configuración → WhatsApp Configuración → "Nueva sesión". Escribe un nombre (ej. negocio) y presiona Crear. Espera unos segundos y aparecerá el botón "Ver QR". Escanéalo con WhatsApp (Dispositivos vinculados → Vincular dispositivo).'),
                        TextEntry::make('wa2')
                            ->label('Estado de la sesión')
                            ->icon('heroicon-o-signal')
                            ->state('Una sesión conectada muestra "Sesión en uso" en verde. Si aparece el botón "Ver QR" nuevamente, significa que se desconectó y hay que volver a escanear.'),
                        TextEntry::make('wa3')
                            ->label('Refrescar QR vencido')
                            ->icon('heroicon-o-arrow-path')
                            ->state('El QR expira en ~60 segundos. Si ves un código borroso o error, usa el botón "Refrescar QR" para generar uno nuevo y escanéalo de inmediato.'),
                        TextEntry::make('wa4')
                            ->label('Cambiar de número de teléfono')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->state('Desconecta la sesión actual con el botón "Desconectar". Luego crea una nueva sesión con otro nombre o edita el "Session ID" en la sección de configuración antes de crear la sesión.'),
                        TextEntry::make('wa5')
                            ->label('Webhook — Auto-detectar hostname')
                            ->icon('heroicon-o-globe-alt')
                            ->state('Después de cada deploy el hostname del contenedor cambia. Usa el botón "Auto-detectar hostname" y luego "Guardar y actualizar webhook" para que WhatsApp siga enviando mensajes al servidor correcto. Sin este paso el chatbot no recibirá mensajes.'),
                        TextEntry::make('wa6')
                            ->label('¿Por qué se desconecta la sesión?')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->state('WhatsApp puede cerrar sesiones remotas por inactividad prolongada, reinicio del servidor o actualización de la app en el teléfono. Si el chatbot deja de responder, verifica primero el estado de la sesión aquí.'),
                    ]),

                Section::make('WhatsApp — Chatbot y Flujo')
                    ->description('Configurar el chatbot conversacional')
                    ->icon('heroicon-o-cpu-chip')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('cb1')
                            ->label('¿Qué hace el chatbot?')
                            ->icon('heroicon-o-information-circle')
                            ->state('Cuando alguien escribe por WhatsApp al número conectado, el chatbot inicia un flujo de preguntas configurado por ti. Al completarlo crea automáticamente un prospecto en el CRM con los datos capturados.'),
                        TextEntry::make('cb2')
                            ->label('Activar o desactivar el chatbot')
                            ->icon('heroicon-o-power')
                            ->state('Ve a Configuración → Flujo del Chatbot. El toggle "Chatbot activo" en la parte superior activa o desactiva el flujo completo. Si está inactivo, los mensajes se reciben pero no se responden automáticamente.'),
                        TextEntry::make('cb3')
                            ->label('Editar textos del flujo')
                            ->icon('heroicon-o-pencil-square')
                            ->state('Cada paso tiene un campo "Mensaje del bot". Edítalo libremente. Puedes usar las variables {nombre} (nombre del prospecto capturado), {servicio} (opción elegida) y {menu} (lista de opciones del menú de servicios).'),
                        TextEntry::make('cb4')
                            ->label('Reordenar los pasos')
                            ->icon('heroicon-o-bars-3')
                            ->state('Arrastra los pasos con el ícono ⠿ a la izquierda de cada tarjeta para cambiar el orden. El número de "Orden" se actualiza solo. Guarda al terminar.'),
                        TextEntry::make('cb5')
                            ->label('Agregar un paso nuevo')
                            ->icon('heroicon-o-plus-circle')
                            ->state('Presiona "+ Agregar paso" al final de la lista. Define el tipo de dato a capturar (texto, email, teléfono, CURP, menú de opciones) y escribe el mensaje que el bot enviará.'),
                        TextEntry::make('cb6')
                            ->label('Menú de servicios (opciones)')
                            ->icon('heroicon-o-list-bullet')
                            ->state('Los pasos de tipo "Menú de opciones" muestran una lista numerada al prospecto. Agrega o edita las opciones en la sección "Opciones del menú" de ese paso. El prospecto responde con el número correspondiente.'),
                        TextEntry::make('cb7')
                            ->label('Campo CURP — opcional')
                            ->icon('heroicon-o-identification')
                            ->state('Cada paso puede marcarse como "¿Requiere CURP?" para vincular esa captura al campo CURP del prospecto. Solo debe existir un paso con esa marca activa.'),
                        TextEntry::make('cb8')
                            ->label('¿Cuándo se crea el prospecto?')
                            ->icon('heroicon-o-user-plus')
                            ->columnSpanFull()
                            ->state('El prospecto se crea en el CRM únicamente cuando el contacto completa TODOS los pasos del flujo. Si abandona la conversación a mitad, la sesión expira en 30 minutos y no se guarda ningún dato. Esto evita prospectos incompletos.'),
                    ]),

                Section::make('API Móvil')
                    ->description('Gestión técnica de la app')
                    ->icon('heroicon-o-signal')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('api1')
                            ->label('Monitor de API')
                            ->icon('heroicon-o-chart-bar-square')
                            ->state('En API Móvil → Monitor puedes ver las últimas llamadas, tokens activos y dispositivos registrados para push notifications.'),
                        TextEntry::make('api2')
                            ->label('Documentación de endpoints')
                            ->icon('heroicon-o-book-open')
                            ->state('En API Móvil → Documentación están todos los endpoints con ejemplos de request y response.'),
                        TextEntry::make('api3')
                            ->label('Configuración')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->state('En API Móvil → Configuración puedes ajustar parámetros como límites de rate y versión activa.'),
                    ]),

            ]);
    }

    // ── Infolist Asesor ───────────────────────────────────────────────────────

    public function asesorInfolist(Schema $schema): Schema
    {
        return $schema
            ->state([])
            ->schema([

                Section::make('Primeros pasos')
                    ->description('Cómo empezar a usar la plataforma')
                    ->icon('heroicon-o-rocket-launch')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('i1')
                            ->label('Inicia sesión')
                            ->icon('heroicon-o-lock-closed')
                            ->state('Usa el correo y contraseña que te dio tu administrador. Si olvidaste tu contraseña, contacta al admin para restablecerla desde el panel web.'),
                        TextEntry::make('i2')
                            ->label('Completa tu perfil')
                            ->icon('heroicon-o-user-circle')
                            ->state('Ve a la pestaña Perfil y llena tu información: teléfono, banco y CLABE interbancaria. Estos datos son necesarios para recibir el pago de tus comisiones.'),
                        TextEntry::make('i3')
                            ->label('Sube tu foto de perfil')
                            ->icon('heroicon-o-camera')
                            ->columnSpanFull()
                            ->state('En Perfil toca tu foto y selecciona "Tomar selfie". La cámara frontal se abrirá automáticamente para actualizar tu foto.'),
                    ]),

                Section::make('Prospectos')
                    ->description('Registra y da seguimiento a clientes')
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('pr1')
                            ->label('Agregar un prospecto')
                            ->icon('heroicon-o-user-plus')
                            ->state('En Inicio toca "+ Nuevo Prospecto". Llena nombre, teléfono, correo y el tipo de crédito que le interesa (FOVISSSTE o INFONAVIT).'),
                        TextEntry::make('pr2')
                            ->label('Simular precalificación')
                            ->icon('heroicon-o-calculator')
                            ->state('Antes de registrar al prospecto usa el Simulador en el menú. Ingresa salario, edad y tipo de crédito para calcular si califica y el monto aproximado.'),
                        TextEntry::make('pr3')
                            ->label('Actualizar estado')
                            ->icon('heroicon-o-arrow-path')
                            ->state('Avanza el estado: Nuevo → Contactado → Calificado → Convertido. Mantén el seguimiento actualizado para que el admin vea tu progreso.'),
                        TextEntry::make('pr4')
                            ->label('Abrir expediente')
                            ->icon('heroicon-o-folder-plus')
                            ->state('Cuando el prospecto esté listo, toca "Abrir Expediente" en su tarjeta. Quedará convertido en expediente activo y desaparecerá de la lista de prospectos.'),
                    ]),

                Section::make('Expedientes')
                    ->description('Gestión de trámites de crédito')
                    ->icon('heroicon-o-folder-open')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('ex1')
                            ->label('Ver mis expedientes')
                            ->icon('heroicon-o-list-bullet')
                            ->state('En la pestaña Expedientes verás solo los asignados a ti. Puedes filtrar por estado o buscar por nombre del cliente.'),
                        TextEntry::make('ex2')
                            ->label('Subir documentos')
                            ->icon('heroicon-o-arrow-up-tray')
                            ->state('Entra al expediente → Documentos → selecciona el tipo (INE, CURP, comprobante, etc.) y toma la foto o elige de tu galería.'),
                        TextEntry::make('ex3')
                            ->label('Ver documentos')
                            ->icon('heroicon-o-eye')
                            ->state('Toca cualquier documento para abrirlo directamente en tu navegador. Los PDFs se muestran en línea sin necesidad de descargarlos.'),
                        TextEntry::make('ex4')
                            ->label('Estado del trámite')
                            ->icon('heroicon-o-arrow-path')
                            ->state('El admin actualiza el estado: En proceso → Documentación → Autorizado → Escrituración → Cerrado. Al cerrarse, verás tu comisión generada.'),
                    ]),

                Section::make('Mapa de Visitas')
                    ->description('Documenta tu actividad en campo')
                    ->icon('heroicon-o-map-pin')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('mv1')
                            ->label('Registrar una visita')
                            ->icon('heroicon-o-plus-circle')
                            ->state('En la pestaña Mapa toca "+ Registrar Visita". La app detecta tu ubicación automáticamente. Agrega una nota y toma fotos del lugar.'),
                        TextEntry::make('mv2')
                            ->label('Tipo de visita')
                            ->icon('heroicon-o-tag')
                            ->state('Selecciona "Cliente" (visita a un prospecto) o "Propiedad" (visita a un inmueble en evaluación).'),
                        TextEntry::make('mv3')
                            ->label('Vincular a un prospecto')
                            ->icon('heroicon-o-link')
                            ->state('Al registrar la visita puedes asociarla a un prospecto para que el admin vea el historial del cliente.'),
                        TextEntry::make('mv4')
                            ->label('Funciona sin internet')
                            ->icon('heroicon-o-signal-slash')
                            ->state('Las visitas y fotos se guardan aunque no tengas señal. Se sincronizan automáticamente cuando recuperes internet.'),
                    ]),

                Section::make('Mis Comisiones')
                    ->description('Consulta tus ingresos')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('co1')
                            ->label('¿Cuándo aparece una comisión?')
                            ->icon('heroicon-o-clock')
                            ->state('Las comisiones aparecen cuando el admin las registra al cerrar un expediente. Verás el monto, fecha y expediente asociado.'),
                        TextEntry::make('co2')
                            ->label('Estados de tu comisión')
                            ->icon('heroicon-o-arrow-path')
                            ->state('Pendiente → generada, no pagada aún. Aprobada → lista para transferencia. Pagada → ya depositada en tu cuenta.'),
                        TextEntry::make('co3')
                            ->label('¿Cómo recibo el pago?')
                            ->icon('heroicon-o-building-library')
                            ->state('El pago se hace vía transferencia a la CLABE de tu perfil. Verifica que esté correcta antes de que se cierre tu primer expediente.'),
                        TextEntry::make('co4')
                            ->label('Resumen mensual')
                            ->icon('heroicon-o-chart-pie')
                            ->state('Al inicio de Comisiones verás: total pendiente, total pagado y número de expedientes cerrados en el mes.'),
                    ]),

                Section::make('Mi Perfil')
                    ->description('Gestiona tu información personal')
                    ->icon('heroicon-o-user-circle')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('pf1')
                            ->label('Editar información')
                            ->icon('heroicon-o-pencil')
                            ->state('En la pestaña Perfil toca cualquier campo para editarlo: nombre, teléfono, banco y CLABE.'),
                        TextEntry::make('pf2')
                            ->label('Cambiar foto de perfil')
                            ->icon('heroicon-o-camera')
                            ->state('Toca tu foto de perfil y selecciona "Tomar selfie". La cámara frontal se activa. La foto se actualiza en toda la plataforma.'),
                        TextEntry::make('pf3')
                            ->label('Cambiar contraseña')
                            ->icon('heroicon-o-lock-closed')
                            ->state('Para cambiar tu contraseña contacta al administrador. Por seguridad, el cambio solo se hace desde el panel web.'),
                        TextEntry::make('pf4')
                            ->label('Cerrar sesión')
                            ->icon('heroicon-o-arrow-right-on-rectangle')
                            ->state('Al final de la pantalla Perfil encontrarás el botón "Cerrar sesión".'),
                    ]),

            ]);
    }
}
