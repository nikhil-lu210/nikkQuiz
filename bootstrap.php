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
