# Consultoría Inmobiliaria (web)

## Versionado (semver)
- El número vive en el archivo `VERSION` y la historia en `CHANGELOG.md`.
- Cada vez que se mezcla a `main`, ANTES de crear el PR:
  1. Sube la versión en `VERSION`: MENOR (1.0.0 → 1.1.0) si hay funciones nuevas,
     PARCHE (1.0.0 → 1.0.1) si solo hay correcciones, MAYOR si rompe algo.
  2. Agrega arriba en `CHANGELOG.md` una sección `## [X.Y.Z] - AAAA-MM-DD` con
     `### Agregado`, `### Cambiado` y/o `### Corregido`, en español y en lenguaje de negocio.
     El encabezado debe ser exacto `## [X.Y.Z]` (lo lee la GitHub Action `release.yml`).
  3. Commit "Versión X.Y.Z" en develop y push.
- Nunca reutilices un número que ya se publicó.
- Al llegar a `main`, `release.yml` crea la etiqueta `vX.Y.Z` y el Release con las notas.
- El commit, rama y fecha del despliegue los escribe el build de Docker en `build.json`
  (`docker/app/write-build-info.php`). Se ven al pie del menú del panel y en `/version.json`.
  Fuera de producción se muestra `vX.Y.Z-dev · commit`.
- La app móvil (`app-consultoriainmobiliaria`) se versiona aparte: ver su `RELEASING.md`.
