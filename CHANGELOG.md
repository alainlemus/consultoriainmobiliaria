# Changelog

Todas las versiones del sistema web. El número vive en el archivo `VERSION` y sigue
[versionado semántico](https://semver.org/lang/es/):

- **Mayor** (2.0.0): cambios que rompen algo para usuarios o integraciones.
- **Menor** (1.1.0): funciones nuevas.
- **Parche** (1.0.1): correcciones.

Cada paso de `develop` a `main` sube la versión y agrega su sección aquí; al llegar
a `main`, la GitHub Action `release.yml` crea la etiqueta `vX.Y.Z` y el Release con
estas notas. Fuera de producción la versión se muestra como `vX.Y.Z-dev · commit`.

La app móvil tiene su propio versionado en su repositorio (`app-consultoriainmobiliaria`).

## [Sin publicar]

### Cambiado
- **Sitio público renovado con animaciones e interacciones estilo Apple:** inicio a pantalla completa con titular que entra palabra por palabra, fondo con zoom lento y parallax, y contenido que se aleja y se desvanece al hacer scroll; barra de navegación de vidrio esmerilado que se compacta al bajar; secciones que aparecen con desenfoque y escala; tarjetas con inclinación 3D y luz que sigue al cursor; línea de tiempo del proceso que se llena con el scroll; botones tipo píldora con efecto magnético; barra de progreso de lectura; cifras que cuentan al entrar en pantalla; y transición suave entre páginas. Aplica al inicio, FOVISSSTE, propiedades, blog, descarga de la app y demás páginas. Se respeta la preferencia de "reducir movimiento" del sistema.

## [1.3.0] - 2026-10-14

### Agregado
- **Documento faltante:** un administrador puede pedir al acreditado un documento que faltaba, aunque los demás ya estén validados, escribiendo una nota que explica cuál es (desde el expediente en la app o desde la tabla de documentos del panel). El acreditado recibe una notificación y ve el aviso en Mi Trámite; mientras no lo suba, el trámite sigue "En revisión", y al subirlo el administrador debe validarlo. El administrador ve en la app también los expedientes ya completos para poder hacerlo. Se puede pedir al acreditado o al asesor (si el acreditado no usa la app, siempre al asesor, que recibe la notificación y el aviso en el expediente).
- **Carpeta por correo:** solo se puede enviar cuando un administrador validó todos los documentos del expediente y no queda ningún documento solicitado pendiente.

### Corregido
- Documentos del acreditado: ahora solo ve los de su sección (los del vendedor o la vivienda no) y, si el asesor ya subió su INE, la que suba él se numera ("INE (2)") en lugar de quedar con el mismo nombre. Iniciar un expediente desde la cola de sincronización (sin señal) también evita duplicados.
- Documentos del acreditado: al subir varios del mismo tipo (por ejemplo los 3 talones de pago) cada uno se guardaba encima del anterior y solo quedaba el último; ahora se conservan todos ("Talón de pago", "Talón de pago (2)"…). Solo se reemplaza el que un administrador rechazó o el que se pidió como faltante, y el archivo nuevo vuelve a revisión.
- Documentos: al reemplazar o volver a subir un documento ya validado, seguía marcado como validado aunque el archivo era otro; ahora regresa a revisión y se borra el archivo anterior.
- Expedientes: iniciar el expediente de un prospecto dos veces (doble toque o reintento por mala señal) creaba dos; ahora se devuelve el abierto. El expediente guarda el correo, CURP y teléfono del prospecto para ligarlo después con su cuenta de acreditado.
- Documentos subidos desde la app del acreditado: la subida respondía con error 500 al avisar al asesor (el archivo sí se guardaba, pero la app mostraba que falló). Ahora termina bien y el aviso lo dice sin asumir quién subió el documento.

### Cambiado
- App del acreditado: el trámite se muestra "En revisión" hasta que un administrador valida todos los documentos; entonces pasa a "En proceso" con el mensaje de que solo debe esperar a que su asesor lo contacte (si se rechaza o falta validar alguno, vuelve a "En revisión").
- Cuando un acreditado sube un documento desde la app, ahora se avisa con notificación push (y en la bandeja del panel) al asesor del expediente y a todos los administradores, que son quienes lo validan.
- App del acreditado: un expediente que el asesor abre en el panel para su prospecto (o con su correo) se liga solo a su cuenta y aparece en la app.
- App del acreditado: el perfil indica si ya envió su solicitud de asesoría (`solicitud_enviada`) para avisarle que ya está registrado.
- Prospectos: el "Servicio de interés" ahora es siempre Crédito FOVISSSTE (campo fijo, también en los prospectos existentes).

## [1.2.0] - 2026-10-13

### Agregado
- **Pagaré:** formato de pagaré (PDF y Word, basado en el documento oficial) llenado con nombre, domicilio y RFC del acreditado y la fecha del día. Disponible en el expediente (grupo Contratos) y en Contratos generados. Importe y vencimiento quedan en blanco.

## [1.1.0] - 2026-10-13

### Cambiado
- **Flujo simplificado:** el asesor solo registra prospectos y sube o escanea sus documentos desde la app; el expediente se crea solo con el primer documento. Se quitan las etapas, el estado y el seguimiento del trámite en la interfaz (los datos se conservan).
- **Panel:** los expedientes muestran cuántos documentos tienen y cuántos están **por capturar**; cada documento se marca como capturado. El menú muestra el total de expedientes. Los PDFs subidos se leen para prellenar datos, sin mover etapas.
- **Tableros y reporte de gestión** (diario, semanal, mensual) ahora miden prospectos, expedientes, documentos subidos y por capturar, por asesor. Se quitan el embudo por etapa, los expedientes sin movimiento y la exportación de KPIs.
- **Comisiones desactivadas:** sin menú, sin cálculo al cerrar, sin avisos ni endpoints (las tablas se conservan).
- **Avisos de validación:** al validar o rechazar un documento se avisa por push al asesor del expediente; al validar el último documento, el asesor recibe "Expediente completo". Los expedientes completos ya no se ven en la app (solo en el panel). El administrador puede pedir desde la app que le llegue por correo la carpeta del expediente (ZIP con enlace de 72 horas).
- **Contratos para todos los asesores:** cualquier asesor puede generar contratos desde la app (Inicio → Contratos) y ve solo los suyos; el administrador ve todos y es el único que edita la plantilla.
- **Validación de documentos:** solo un administrador valida o rechaza (con motivo) desde el panel o la app; el motivo ya no pisa la nota que identifica el documento. Los archivos del mismo tipo subidos juntos ya no se pisan.
- App del acreditado: su trámite aparece "En revisión".

## [1.0.0] - 2026-10-13

### Agregado
- Versionado del sistema: número en el archivo `VERSION`, historial en este CHANGELOG, y el panel muestra al pie del menú la versión, el commit y la fecha del despliegue. `/version.json` lo expone para monitoreo.
