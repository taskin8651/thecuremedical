<?php

/**
 * Patches two framework files of a fresh Laravel 10 skeleton:
 *   config/app.php        - registers App\Providers\CureServiceProvider
 *   app/Http/Kernel.php   - adds route middleware aliases, enables AuthenticateSession
 *   app/Providers/RouteServiceProvider.php - HOME = /admin/dashboard
 * Safe to run twice.
 */
$root = $argv[1] ?? getcwd();

// ---- config/app.php
$file = $root.'/config/app.php';
$src = file_get_contents($file);
if (! str_contains($src, 'CureServiceProvider')) {
    $needle = 'App\Providers\RouteServiceProvider::class,';
    if (! str_contains($src, $needle)) {
        fwrite(STDERR, "Could not find RouteServiceProvider in config/app.php\n");
        exit(1);
    }
    $src = str_replace($needle, $needle."\n        App\Providers\CureServiceProvider::class,", $src);
    file_put_contents($file, $src);
    echo "patched config/app.php\n";
}

// ---- app/Http/Kernel.php
$file = $root.'/app/Http/Kernel.php';
$src = file_get_contents($file);
if (! str_contains($src, 'staff.active')) {
    $needle = 'protected $middlewareAliases = [';
    if (! str_contains($src, $needle)) {
        fwrite(STDERR, "Could not find \$middlewareAliases in app/Http/Kernel.php\n");
        exit(1);
    }
    $aliases = <<<'PHP'

        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        'staff.active' => \App\Http\Middleware\EnsureStaffIsActive::class,
        'secure.headers' => \App\Http\Middleware\SecurityHeaders::class,
PHP;
    $src = str_replace($needle, $needle.$aliases, $src);
    file_put_contents($file, $src);
    echo "patched app/Http/Kernel.php\n";
}

// ---- web middleware group: AuthenticateSession (makes "sign out other devices" effective)
$file = $root.'/app/Http/Kernel.php';
$src = file_get_contents($file);
$cls = '\\Illuminate\\Session\\Middleware\\AuthenticateSession::class,';
$active = false;
foreach (explode("\n", $src) as $line) {
    if (trim($line) === $cls) {
        $active = true;
    }
}
if (! $active) {
    $lines = explode("\n", $src);
    $done = false;
    foreach ($lines as $i => $line) {
        if (trim($line) === '// '.$cls) {
            $lines[$i] = str_replace('// ', '', $line);
            $done = true;
            break;
        }
    }
    if (! $done) {
        foreach ($lines as $i => $line) {
            if (trim($line) === '\\Illuminate\\Session\\Middleware\\StartSession::class,') {
                array_splice($lines, $i + 1, 0, [str_replace(trim($line), $cls, $line)]);
                break;
            }
        }
    }
    file_put_contents($file, implode("\n", $lines));
    echo "enabled AuthenticateSession\n";
}

// ---- RouteServiceProvider: signed-in staff who open /login go to the dashboard
$file = $root.'/app/Providers/RouteServiceProvider.php';
$src = file_get_contents($file);
if (str_contains($src, "HOME = '/home'")) {
    file_put_contents($file, str_replace("HOME = '/home'", "HOME = '/admin/dashboard'", $src));
    echo "patched RouteServiceProvider::HOME\n";
}

echo "patching complete\n";
