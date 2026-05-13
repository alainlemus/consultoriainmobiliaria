<x-mail::message>
# Hola, {{ $nombreCliente }}

Gracias por confiar en **{{ $nombreEmpresa }}** para gestionar tu trámite inmobiliario.

Tu opinión es muy valiosa para nosotros y ayuda a que otras familias conozcan nuestro servicio. ¿Nos regalas un momento para contarnos cómo fue tu experiencia?

<x-mail::button :url="$link" color="primary">
Dejar mi testimonio
</x-mail::button>

Este enlace es **personal e intransferible**, solo funciona una vez y estará disponible durante **{{ $expiraDias }} días**.

---

Si el botón no funciona, copia y pega este enlace en tu navegador:

`{{ $link }}`

Gracias por tu tiempo,<br>
**El equipo de {{ $nombreEmpresa }}**

<x-mail::subcopy>
Si no realizaste ningún trámite con nosotros o crees que este correo es un error, puedes ignorarlo con total seguridad.
</x-mail::subcopy>
</x-mail::message>
