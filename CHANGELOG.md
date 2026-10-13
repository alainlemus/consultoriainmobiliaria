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
- **Flujo simplificado:** el asesor solo registra prospectos y sube o escanea sus documentos desde la app; el expediente se crea solo con el primer documento. Se quitan las etapas, el estado y el seguimiento del trámite en la interfaz (los datos se conservan).
- **Panel:** los expedientes muestran cuántos documentos tienen y cuántos están **por capturar**; cada documento se marca como capturado. El menú muestra el total de expedientes. Los PDFs subidos se leen para prellenar datos, sin mover etapas.
- **Tableros y reporte de gestión** (diario, semanal, mensual) ahora miden prospectos, expedientes, documentos subidos y por capturar, por asesor. Se quitan el embudo por etapa, los expedientes sin movimiento y la exportación de KPIs.
- **Comisiones desactivadas:** sin menú, sin cálculo al cerrar, sin avisos ni endpoints (las tablas se conservan).
- App del acreditado: su trámite aparece "En revisión".

## [1.0.0] - 2026-10-13

### Agregado
- Versionado del sistema: número en el archivo `VERSION`, historial en este CHANGELOG, y el panel muestra al pie del menú la versión, el commit y la fecha del despliegue. `/version.json` lo expone para monitoreo.
