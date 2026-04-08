<?php

declare(strict_types=1);

/**
 * Autoloads `app/{ClassName}.php` and defines the project root constant.
 */
if (!defined('NIKKQUIZ_ROOT')) {
    define('NIKKQUIZ_ROOT', __DIR__);
}

spl_autoload_register(static function (string $class): void {
    $file = NIKKQUIZ_ROOT . '/app/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

/**
 * Web path prefix for this app (e.g. '' or '/nikkQuiz'). Used for redirects and absolute paths.
 */
function nikkquiz_base_uri(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $script = str_replace('\\', '/', (string) $script);
    $dir = dirname($script);
    if (str_ends_with($script, '/api/handler.php') || str_ends_with($script, '/api/handler')) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '.') {
        return '';
    }

    return $dir;
}

/** Absolute URL path to a page without .php (e.g. /nikkQuiz/batch). */
function nikkquiz_path(string $page): string
{
    $page = ltrim($page, '/');
    $base = nikkquiz_base_uri();

    return ($base === '' ? '' : $base) . '/' . $page;
}
