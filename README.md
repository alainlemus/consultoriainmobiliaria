# Consultoría Inmobiliaria

Plataforma web y sistema de gestión para una consultoría inmobiliaria especializada en créditos INFONAVIT y FOVISSSTE, avalúos comerciales y fiscales, y gestión de escrituras en Hidalgo, Veracruz y San Luis Potosí.

**Producción:** https://consultoriainmobiliaria.com.mx

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12 + PHP 8.4 |
| Panel admin | Filament v5 |
| Permisos | Filament Shield v4 |
| Frontend landing | Blade + Alpine.js + Tailwind CSS v4 |
| Base de datos | MySQL |
| Colas | Laravel Queue (`database` en prod, `sync` en local) |
| Correo | Resend |
| Push notifications | FCM HTTP v1 (OAuth2 + JWT manual) |
| App móvil | React Native + Expo (carpeta `app-consultoriainmobiliaria/`) |
| Deploy | Dokploy en VPS |
| Local | Laravel Herd |

---

## Módulos del sistema

### Landing pública

- **Home** — hero, servicios, testimonios, CTA WhatsApp dinámico
- **Propiedades** — listado con filtros + detalle individual con JSON-LD `RealEstateListing`
- **Blog** — posts con JSON-LD `Article`
- **Aviso de privacidad**
- **SEO completo** — meta tags, Open Graph, Twitter Card, JSON-LD `RealEstateAgent` global, sitemap XML, robots.txt
- **GA4** — carga condicional tras consentimiento de cookies
- **Banner de cookies** — consentimiento con `localStorage` + evento `cookies:accepted`

### Panel admin (`/admin`) — rol `super_admin`

- Dashboard con KPIs globales (expedientes, prospectos, comisiones)
- Gestión de **Expedientes** (flujo completo: prospecto → trámite → cierre)
- Gestión de **Prospectos**
- Gestión de **Comisiones**
- Gestión de **Asesores** (usuarios con rol `asesor`)
- Gestión de **Propiedades** (CRUD + imágenes)
- Gestión de **Blog** (posts con editor rich text)
- **Contratos** y **Testimonios**
- **Ajustes Generales** — configuración del sitio via settings cacheados
- **Reportes** — enviados por correo automáticamente (diario lun–vie 20:00, semanal sáb 08:00, mensual último día 20:00)
- **API Móvil** — endpoints para la app React Native

### Panel asesor (`/admin`) — rol `asesor`

- Solo ve sus propios expedientes, prospectos y visitas
- Comisiones en modo solo lectura
- No puede eliminar prospectos

---

## Configuración local

### Requisitos

- PHP 8.4 (Laravel Herd)
- MySQL
- Node.js + npm

### Instalación

```bash
git clone <repo>
cd consultoriaInmobiliaria

composer install
npm install --legacy-peer-deps

cp .env.example .env
php artisan key:generate

# Configurar DB en .env, luego:
php artisan migrate --seed

npm run dev
```

### Variables de entorno relevantes

```env
APP_ENV=local
APP_URL=http://consultoriainmobiliaria.test

DB_DATABASE=consultoria_inmobiliaria

QUEUE_CONNECTION=sync       # usar 'database' en producción

RESEND_API_KEY=...
RESEND_FROM_ADDRESS=...

FIREBASE_PROJECT_ID=consultoria-inmobiliaria-51375
FIREBASE_CLIENT_EMAIL=...
FIREBASE_PRIVATE_KEY=...
```

---

## Seeders

```bash
php artisan db:seed --class=SuperAdminRoleSeeder   # rol super_admin con todos los permisos
php artisan db:seed --class=AsesorRoleSeeder        # rol asesor con 16 permisos restringidos
```

---

## Tests

```bash
php artisan test
# 170 passed, 1 skipped
```

Los tests usan prefijo `test_` en el nombre del método (PHPUnit 12, sin `@test`).

---

## Deploy a producción

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan optimize:clear    # obligatorio post-deploy (Shield cachea permisos)
```

> **Nota:** Si se cambia `correo_contacto` u otro setting en el admin, ejecutar `php artisan cache:clear` en el VPS — los settings usan `Cache::rememberForever`.

### Cron (VPS)

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### Queue worker (supervisor en VPS)

```bash
php artisan queue:work --sleep=3 --tries=3
```

---

## App móvil

Directorio: `app-consultoriainmobiliaria/`

- React Native + Expo
- Autenticación con Laravel Sanctum
- Push notifications via FCM
- Biometría con `expo-local-authentication`
- Build Android: `eas build --profile development --platform android`
- Build iOS: pendiente de Apple Developer Account

---

## Notas importantes

- `setting('clave')` usa `Cache::rememberForever` — limpiar caché si se cambia un valor desde el admin
- Shield v4: `define_via_gate: false` — `super_admin` requiere permisos explícitos, no bypass automático
- `APP_ENV=production` activa indexación en sitemap (sin `X-Robots-Tag: noindex`)
- El dominio `consultoriainmobiliaria.com.mx` no tiene registros MX — no puede recibir correo, solo enviar
