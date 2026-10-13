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

## [1.0.0] - 2026-10-13

### Agregado
- Versionado del sistema: número en el archivo `VERSION`, historial en este CHANGELOG, y el panel muestra al pie del menú la versión, el commit y la fecha del despliegue. `/version.json` lo expone para monitoreo.
