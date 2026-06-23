@extends('layouts.app')

@section('seo_title', 'Eliminación de datos — Consultoría Inmobiliaria')

@section('content')
    <section class="py-20 bg-cream-50 min-h-screen" style="padding-top: 140px;">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">
                <p class="section-subtitle text-gold-400 mb-3">Legal</p>
                <h1 class="section-title mb-4">Eliminación de <span class="text-gold-400">Datos</span></h1>
                <div class="gold-divider"></div>
            </div>

            <div class="bg-white rounded-sm shadow-sm border border-cream-200 p-8 sm:p-12 space-y-8">

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-3">Solicitud de eliminación de datos</h2>
                    <p class="text-gray-600 leading-relaxed">
                        Si eres usuario de la aplicación <strong>Consultoría Inmobiliaria</strong> y deseas solicitar
                        la eliminación de tu cuenta y todos los datos asociados, puedes hacerlo directamente
                        desde la app siguiendo estos pasos:
                    </p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pasos para solicitar la eliminación desde la app</h3>
                    <ol class="list-decimal list-inside space-y-3 text-gray-600">
                        <li>Abre la aplicación <strong>Consultoría Inmobiliaria</strong> en tu dispositivo.</li>
                        <li>Inicia sesión con tu cuenta.</li>
                        <li>Ve a la sección <strong>Mi Perfil</strong> (ícono de usuario en la parte inferior).</li>
                        <li>Desplázate hasta el final de la pantalla.</li>
                        <li>Toca la opción <strong>"Solicitar cancelación de cuenta"</strong>.</li>
                        <li>Confirma tu solicitud. Recibirás una notificación de que tu solicitud fue recibida.</li>
                    </ol>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Solicitud por correo electrónico</h3>
                    <p class="text-gray-600 leading-relaxed">
                        También puedes solicitar la eliminación de tus datos enviando un correo a:
                        <a href="mailto:contacto@consultoriainmobiliaria.com.mx"
                           class="text-gold-400 font-semibold hover:underline">
                            contacto@consultoriainmobiliaria.com.mx
                        </a>
                        indicando tu nombre completo y el correo con el que registraste tu cuenta.
                        Procesaremos tu solicitud en un plazo máximo de <strong>30 días hábiles</strong>.
                    </p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Datos que se eliminarán</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-600">
                        <li>Nombre, correo electrónico y número de teléfono</li>
                        <li>Foto de perfil</li>
                        <li>Token de sesión y dispositivos registrados</li>
                        <li>Historial de actividad dentro de la app</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Datos que se conservarán</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Por obligaciones legales y fiscales, algunos datos relacionados con operaciones
                        inmobiliarias realizadas (expedientes, contratos y comisiones) podrán conservarse
                        por un período de hasta <strong>5 años</strong> conforme a la legislación mexicana aplicable,
                        antes de ser eliminados definitivamente.
                    </p>
                </div>

                <div class="border-t border-cream-200 pt-6">
                    <p class="text-sm text-gray-400">
                        Consultoría Inmobiliaria — México.<br>
                        Para más información consulta nuestro
                        <a href="{{ route('aviso.privacidad') }}" class="text-gold-400 hover:underline">Aviso de Privacidad</a>.
                    </p>
                </div>

            </div>
        </div>
    </section>
@endsection
