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
