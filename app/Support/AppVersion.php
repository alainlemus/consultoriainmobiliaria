<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Versión del sistema que se muestra en el panel.
 *
 * - El número (semver) vive en el archivo VERSION y se sube en cada paso a producción.
 * - El commit, la rama y la fecha de cada despliegue los escribe el build de Docker en
 *   build.json (docker/app/write-build-info.php); en local se leen de .git.
 * - Fuera de producción se marca como "-dev" con el commit, para distinguir cada
 *   despliegue del ambiente de pruebas.
 */
class AppVersion
{
    /** @var array<string, mixed>|null */
    protected static ?array $build = null;

    public static function number(): string
    {
        $path = base_path('VERSION');
        $version = is_file($path) ? trim((string) file_get_contents($path)) : '';

        return preg_match('/^\d+\.\d+\.\d+/', $version) ? $version : '0.0.0';
    }

    public static function commit(): ?string
    {
        $commit = static::build()['commit'] ?? null;

        return $commit ? substr($commit, 0, 7) : null;
    }

    public static function fullCommit(): ?string
    {
        return static::build()['commit'] ?? null;
    }

    public static function branch(): ?string
    {
        return static::build()['branch'] ?? null;
    }

    public static function builtAt(): ?Carbon
    {
        $at = static::build()['built_at'] ?? null;

        return $at ? Carbon::parse($at) : null;
    }

    public static function isProduction(): bool
    {
        return app()->isProduction();
    }

    /** "v1.2.0" en producción; "v1.2.0-dev · a1b2c3d" en los demás ambientes. */
    public static function label(): string
    {
        if (static::isProduction()) {
            return 'v'.static::number();
        }

        return 'v'.static::number().'-dev'.(static::commit() ? ' · '.static::commit() : '');
    }

    /**
     * @return array{version: string, label: string, environment: string, commit: ?string, branch: ?string, built_at: ?string}
     */
    public static function toArray(): array
    {
        return [
            'version' => static::number(),
            'label' => static::label(),
            'environment' => (string) app()->environment(),
            'commit' => static::fullCommit(),
            'branch' => static::branch(),
            'built_at' => static::builtAt()?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function build(): array
    {
        if (static::$build !== null) {
            return static::$build;
        }

        $file = base_path('build.json');

        if (is_file($file)) {
            return static::$build = json_decode((string) file_get_contents($file), true) ?: [];
        }

        return static::$build = static::fromGit();
    }

    /**
     * En local (sin build de Docker) se lee el commit directo de .git.
     *
     * @return array<string, mixed>
     */
    protected static function fromGit(): array
    {
        $git = base_path('.git');

        if (! is_file($git.'/HEAD')) {
            return [];
        }

        $head = trim((string) file_get_contents($git.'/HEAD'));

        if (! str_starts_with($head, 'ref: ')) {
            return ['commit' => preg_match('/^[0-9a-f]{40}$/', $head) ? $head : null];
        }

        $ref = substr($head, 5);
        $commit = is_file($git.'/'.$ref) ? trim((string) file_get_contents($git.'/'.$ref)) : null;

        return ['commit' => $commit ?: null, 'branch' => preg_replace('#^refs/heads/#', '', $ref)];
    }

    /** Para pruebas. */
    public static function flush(): void
    {
        static::$build = null;
    }
}
