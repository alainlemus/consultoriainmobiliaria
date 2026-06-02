# Proyecto: Consultoría Inmobiliaria CRM

## Stack
- Laravel 12 + PHP 8.4
- **Filament v5** (IMPORTANTE: namespaces distintos a v4)
- Livewire 3
- MySQL

## Filament v5 — Namespaces correctos

### Layout / Schema components
```php
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
```

### Form fields (sin cambio)
```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
```

### Acciones (Actions) — YA NO son `Filament\Tables\Actions\*`
```php
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
```

### Table columns
```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
```

### Get/Set reactivos en formularios
```php
// En Filament v5 usar:
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

// NO usar:
// use Filament\Forms\Get;
// use Filament\Forms\Set;
```

## Reglas de desarrollo
- Siempre `git push` después de cada commit
- No afectar producción al hacer cambios en develop
- Deploy flow: push `develop` → auto-deploy staging; merge a `main` → producción
- Después de cada deploy en staging: actualizar hostname del webhook desde el panel (Configuración → WhatsApp → Auto-detectar)
