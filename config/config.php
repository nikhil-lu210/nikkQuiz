<?php

declare(strict_types=1);

/**
 * Application configuration. Optional overrides:
 * - Project root: ../config.local.php (legacy)
 * - This directory: config.local.php
 *
 * @return array{site_password?: ?string, site_password_hash?: ?string, sqlite_path?: string}
 */
return (static function (): array {
    $root = dirname(__DIR__);
    $defaults = [
        'site_password' => null,
        'site_password_hash' => null,
        'sqlite_path' => $root . '/data/nikkquiz.sqlite',
    ];

    $locals = [
        $root . '/config.local.php',
        __DIR__ . '/config.local.php',
    ];

    $cfg = $defaults;
    foreach ($locals as $local) {
        if (!is_file($local)) {
            continue;
        }
        $patch = require $local;
        if (is_array($patch)) {
            $cfg = array_merge($cfg, $patch);
        }
    }

    return $cfg;
})();
