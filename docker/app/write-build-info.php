<?php

/**
 * Se corre al construir la imagen (Dockerfile.prod / Dockerfile.dev): guarda en
 * build.json de qué commit y rama salió este despliegue y cuándo se construyó, para
 * mostrarlo junto a la versión. Lee .git directamente (sin necesitar git instalado);
 * si no está disponible, se puede pasar con los build args GIT_COMMIT / GIT_BRANCH.
 */
$root = dirname(__DIR__, 2);
$commit = getenv('GIT_COMMIT') ?: null;
$branch = getenv('GIT_BRANCH') ?: null;
$git = $root.'/.git';

if (! $commit && is_file($git.'/HEAD')) {
    $head = trim((string) file_get_contents($git.'/HEAD'));

    if (str_starts_with($head, 'ref: ')) {
        $ref = substr($head, 5);
        $branch ??= preg_replace('#^refs/heads/#', '', $ref);

        if (is_file($git.'/'.$ref)) {
            $commit = trim((string) file_get_contents($git.'/'.$ref));
        } elseif (is_file($git.'/packed-refs')) {
            foreach (file($git.'/packed-refs') as $line) {
                if (str_ends_with(trim($line), ' '.$ref)) {
                    $commit = strtok($line, ' ');
                    break;
                }
            }
        }
    } elseif (preg_match('/^[0-9a-f]{40}$/', $head)) {
        $commit = $head; // HEAD suelto (clonado en un commit específico)
    }
}

$info = [
    'commit' => $commit && preg_match('/^[0-9a-f]{7,40}$/', $commit) ? $commit : null,
    'branch' => $branch ?: null,
    'built_at' => gmdate('c'),
];

file_put_contents($root.'/build.json', json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

echo 'Build: '.json_encode($info).PHP_EOL;
