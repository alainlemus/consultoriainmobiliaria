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

## [1.12.1] - 2026-11-09

### Agregado
- CRM → «Rendimiento de QR»: embudo de los QR impresos (escaneos, registros, solicitudes, expedientes y conversión) por asesor y por medio, con gráfica semanal y filtros de fechas, asesor y medio. El asesor solo ve lo suyo.

## [1.12.0] - 2026-11-09

### Agregado
- CRM → «Códigos QR»: un QR por asesor y por medio (lona, tarjeta, panfleto, anuncio) para la publicidad impresa. Se descarga en PNG, SVG (imprenta, lonas) y PDF con el código en texto grande. Cada fila muestra el embudo: escaneos, registros en la app, solicitudes y expedientes.
- Liga corta `/a/{codigo}`: cuenta el escaneo y manda a Play Store (con `referrer` del asesor) o App Store según el celular; desde computadora o con la tienda sin configurar va a «Descargar app» mostrando el código.
- El acreditado puede escribir su «código de asesor» al crear la cuenta en la app (requiere app 2.10.0). Queda ligado al asesor y su solicitud de asesoría nace asignada a él con origen «QR impreso». Si el prospecto ya tenía otro asesor, no se pisa: se agrega una nota para que admin decida.
- API pública `GET /api/v1/acreditado/codigo-asesor/{codigo}` para que la app confirme el nombre del asesor mientras se teclea el código.
- El asesor ve en el panel solo sus QR y puede descargarlos; crear, editar y borrar es de super_admin.
- Atribución automática de la instalación (fase 2): `POST /api/v1/acreditado/atribucion` cruza la instalación nueva con los escaneos de las últimas 48 h (misma IP, plataforma, versión del sistema y modelo). Si es ambiguo no adivina. Cada escaneo se atribuye una sola vez. El registro guarda cómo llegó el código (tecleado, Play Store o huella) y la columna «Registros» del panel muestra cuántos se detectaron solos.

- La liga del QR abre la app directo si ya está instalada: el dominio publica `/.well-known/apple-app-site-association` y `/.well-known/assetlinks.json`. El Team ID de Apple y la huella SHA-256 de Google Play se capturan en Panel → App móvil.

### Corregido
- Crear cuenta de acreditado sin CURP ya no falla con error 500.

## [1.11.1] - 2026-11-06

### Cambiado
- Blog «Infonavit vs Fovissste»: nuevo título y descripción para buscadores, tabla comparativa al inicio y preguntas frecuentes. La dirección del artículo no cambia.
- `/propiedades` queda con `noindex, follow` mientras no haya propiedades en venta, y vuelve a indexarse sola al publicar la primera.

### Agregado
- Estilos de tabla para el contenido del blog.

### Importante
- Requiere la migración `mejorar_seo_post_infonavit_vs_fovissste`, que actualiza el artículo en la base de datos.

## [1.11.0] - 2026-11-06

### Agregado
- Encargos de gestor: nuevo estado **Por validar**. El gestor avisa con «Ya terminé» que entregó todo (`POST /v1/gestor/encargos/{id}/entregar`) y puede reabrir el encargo si olvidó algo (`POST /v1/gestor/encargos/{id}/reabrir`). Los super_admin reciben aviso en ambos casos.
- Recordatorio a los super_admin de los encargos entregados sin validar: comando `encargos:recordar-validacion`, de lunes a viernes a las 9 am de México, a los 2 días de la entrega y luego cada 2 días.

### Cambiado
- Un encargo ya no se cierra solo al validar lo subido: se termina únicamente si el gestor tocó «Ya terminé» y todo está validado. Antes, validar el primer documento podía cerrarlo y el gestor ya no podía subir los demás.
- Rechazar un documento de un encargo por validar se lo regresa al gestor (En trámite) para que lo vuelva a subir.

### Importante
- Requiere la migración `add_por_validar_to_encargos_gestor` y la app con el botón «Ya terminé»: con una versión anterior de la app, los encargos no se pueden cerrar.

## [1.10.4] - 2026-11-04

### Corregido
- Mapa de anuncios en la app: cada asesor solo ve los anuncios que él colocó; antes veía los de todos los asesores. El super_admin sigue viendo los de todos (incluidos los retirados).

## [1.10.3] - 2026-11-04

### Cambiado
- El panel ya no muestra en el menú «WhatsApp Configuración» ni «Flujo del Chatbot» (y sus secciones en «Ayuda y Tutorial»), porque por ahora no se usan. No se borró nada: la configuración se conserva y basta con quitar el ocultamiento para volver a mostrarlas.

## [1.10.2] - 2026-11-04

### Agregado
- Presentaciones de la app (paso 4 de Primeros pasos) en `public/presentaciones`: video y PDF de «Crédito Infonavit paso a paso» y de «Tu crédito Fovissste, explicado». Ya viajan con el despliegue; antes había que subirlas a mano.

## [1.10.1] - 2026-11-04

### Corregido
- La ruta del asesor usa el día y la hora de México: una visita después de las 6 pm ya no aparece en la ruta del día siguiente y las horas ya no salen 6 horas adelantadas (app y panel).
- Las mayúsculas respetan los acentos: el servicio del prospecto se ve «CRÉDITO FOVISSSTE» (antes «CRéDITO»), igual en el panel y en el contrato en Word.

## [1.10.0] - 2026-11-03

### Agregado
- Nuevo rol **gestor**: la persona que hace los trámites en las dependencias públicas. Lo crea el administrador en Usuarios; entra al panel y a la app y solo ve los expedientes que le envían.
- **Enviar a gestor**: en el expediente (pestaña Gestores o botón de arriba) el super_admin elige al gestor, qué tramitar, la fecha de entrega, los documentos que verá y qué datos del expediente comparte. Un expediente puede tener varios gestores a la vez.
- **Revisión de ida y vuelta**: el gestor revisa cada documento y marca los incorrectos con una nota; el administrador sube el correcto desde "Corregir y reenviar" y el gestor solo revisa lo que cambió. Con todo correcto empieza el trámite.
- El gestor sube lo que tramita (PDF, Word o imagen) y el administrador lo valida o rechaza en Documentos; el encargo se cierra solo cuando todo está validado. Historial de cada vuelta.
- Avisos en la campana del panel y en la app: al gestor cuando le envían o regresan un expediente y cuando le validan o rechazan algo; a todos los super_admin cuando el gestor marca documentos mal, empieza el trámite o sube un documento.
- Instrucciones claras para el super_admin (pestaña Gestores) y para el gestor (su página "Mis expedientes").

### Cambiado
- El logo del panel lleva al lado "Consultoría / Inmobiliaria" en dos renglones.
- "Configuración CRM" y "API Móvil" van al final del menú y siempre inician cerrados.

### Seguridad
- Un gestor no puede usar la API del asesor (crear prospectos, expedientes o contratos) aunque inicie sesión con el mismo login.

## [1.9.0] - 2026-11-03

### Agregado
- La app del acreditado puede adjuntar la captura del simulador oficial (Infonavit o Fovissste) al pedir que un asesor lo llame; la captura aparece en el prospecto igual que cuando la sube el asesor.

### Corregido
- Registrarse en la app con una CURP o un correo que ya tienen cuenta muestra un mensaje claro en lugar de un error técnico.

## [1.8.0] - 2026-11-01

### Agregado
- Páginas de servicio para posicionamiento en Google: avalúos inmobiliarios, crédito INFONAVIT y escrituración y gestoría (`/servicios/...`), con preguntas frecuentes, pasos del trámite, artículos relacionados y botón de WhatsApp.
- Páginas por zona de cobertura: Huejutla de Reyes, Pachuca (con Tula e Ixmiquilpan), Huasteca veracruzana y San Luis Potosí (`/consultoria-inmobiliaria/...`), para aparecer en búsquedas locales.
- Cada artículo del blog termina con una invitación a contratar el servicio relacionado con su categoría (avalúos, INFONAVIT o FOVISSSTE).
- Las páginas nuevas se incluyen en el sitemap y se enlazan desde el pie de página y desde las tarjetas de servicios de la portada.

### Cambiado
- La portada ahora lleva "Consultoría Inmobiliaria en Hidalgo | Crédito INFONAVIT, FOVISSSTE y Avalúos" como título y la palabra clave en el encabezado principal; el eslogan "Tu patrimonio, nuestra prioridad" se mantiene igual a la vista.
- El título de la página de propiedades ya no repite el nombre de la empresa, para que no compita con la portada en Google.
- Los títulos de los artículos del blog ya no llevan el sufijo "— Blog Inmobiliario".

### Corregido
- Entrar con `www.` redirige de forma permanente al dominio sin `www`, para que Google no vea dos copias del sitio.
- El bloque de datos estructurados del proceso paso a paso generaba JSON inválido (números con cero a la izquierda).
- La dirección canónica de la portada ahora coincide con la que elige Google (con diagonal final).

## [1.7.0] - 2026-10-30

### Agregado
- Oficio de "Regresión de fases" para FOVISSSTE (AT'N Hipotecaria Hogar) en cada contrato generado: lleva los nombres del acreditado y del obligado solidario, la ciudad y la fecha; el número de crédito se llena a mano. Se ve en el panel ("Regresión de fases (PDF)" en Contratos generados) y en la app.

### Cambiado
- Pagaré con el formato nuevo: incluye datos del deudor (nombre, domicilio e INE con clave de elector), del aval (el obligado solidario), lugar y fecha de suscripción, el acreedor y su domicilio de pago, y espacio para dos testigos. El importe, el vencimiento, el % de intereses mensual y las firmas se llenan a mano. Aplica al PDF y al Word del panel y al pagaré de la app.

## [1.6.1] - 2026-10-26

### Corregido
- Los roles con permiso de contratos (por ejemplo "Contratos") ya no ven "403 Prohibido" al entrar al panel: aterrizan en "Contratos generados", pueden verlos y descargar el pagaré y el Word. Cada rol llega a la primera sección que tiene permitida.

## [1.6.0] - 2026-10-26

### Agregado
- App móvil: el pagaré de cada contrato generado se puede ver y compartir desde el historial de contratos, también en contratos anteriores; el servidor lo arma con los datos del contrato.

### Corregido
- Pagaré en PDF del panel (contratos generados y expedientes): ya no sale pegado a las orillas de la hoja; ahora lleva márgenes como el de la app y cabe en una sola página.

## [1.5.2] - 2026-10-26

### Cambiado
- Panel: la página "Contratos para clientes" ahora se llama "Configuración de contrato" (en el menú, en su título y en la Ayuda).

## [1.5.1] - 2026-10-14

### Corregido
- Sitio público: al cambiar de página el logo del menú ya no aparece gigante por un instante antes de ajustarse; ahora conserva su tamaño desde que carga.

## [1.5.0] - 2026-10-14

### Cambiado
- Sitio público: la opción "Propiedades" del menú (escritorio y móvil) y su entrada en el sitemap solo aparecen si hay al menos una propiedad en venta; al vender, ocultar o eliminar la última desaparece sola.
- **Correos renovados con el diseño del sitio:** confirmación de contacto, aviso de nuevo contacto, carpeta del expediente, reporte de gestión, solicitud de testimonio, mensajes masivos y restablecimiento de contraseña (asesores, acreditados y panel) ahora comparten la cabecera oscura con dorado, títulos grandes, tarjetas redondeadas, botones tipo píldora y un pie con el lema y la cobertura. Se ven bien en celular.
- **PDF del reporte de gestión rediseñado** con la misma imagen: cabecera oscura con el escudo y el dorado, título grande, tarjetas con las cifras del período, secciones con barra dorada, tablas con encabezado oscuro y pie con el lema.
- En los correos de contacto el servicio se muestra con su nombre completo ("Crédito FOVISSSTE" en lugar de "Fovissste").

### Corregido
- **Logo en los correos:** antes se cargaba desde la dirección pública del sitio, así que no se veía en pruebas locales, en staging ni cuando el cliente de correo bloquea imágenes remotas. Ahora viaja incrustado dentro del mensaje (en una copia liviana de 256 px) y siempre se ve; si no hay logo configurado se usa el escudo de respaldo.

## [1.4.1] - 2026-10-14

### Corregido
- Sitio público: la barra inferior del pie de página (derechos reservados, descargar app, aviso de privacidad) no se veía porque el pie quedaba mal cerrado cuando hay redes sociales configuradas; ahora se ve sobre fondo oscuro y legible.
- Sitio público: en "El proceso paso a paso" la línea solo cubría la primera fila de pasos y no se llenaba completa; ahora cada fila tiene su línea y se llena con el scroll.

## [1.4.0] - 2026-10-14

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
