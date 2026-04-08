<?php

declare(strict_types=1);

/**
 * Loads optional local overrides. Copy config.local.php.example to config.local.php (never commit).
 *
 * @return array{site_password?: ?string, site_password_hash?: ?string}
 */
return (static function (): array {
    $defaults = [
        'site_password' => null,
        'site_password_hash' => null,
    ];

    $local = dirname(__FILE__) . '/config.local.php';
    if (!is_file($local)) {
        return $defaults;
    }

    $cfg = require $local;
    if (!is_array($cfg)) {
        return $defaults;
    }

    return array_merge($defaults, $cfg);
})();
