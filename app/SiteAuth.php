<?php

declare(strict_types=1);

/**
 * Single shared "site password" for teacher/admin UI and API.
 * Student flows (quiz links + PIN) do not require this.
 */
final class SiteAuth
{
    public const SESSION_KEY = 'nikk_site_authenticated';

    /** @return array{site_password?: ?string, site_password_hash?: ?string} */
    public static function config(): array
    {
        static $cfg;
        if ($cfg === null) {
            $cfg = require dirname(__DIR__) . '/config.php';
        }

        return $cfg;
    }

    public static function isConfigured(): bool
    {
        $c = self::config();
        $plain = $c['site_password'] ?? null;
        $hash = $c['site_password_hash'] ?? null;

        return (is_string($plain) && $plain !== '')
            || (is_string($hash) && $hash !== '');
    }

    public static function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION[self::SESSION_KEY]);
    }

    public static function verifyPassword(string $plain): bool
    {
        $c = self::config();
        $hash = $c['site_password_hash'] ?? null;
        if (is_string($hash) && $hash !== '') {
            return password_verify($plain, $hash);
        }

        $pwd = $c['site_password'] ?? '';
        if (!is_string($pwd) || $pwd === '') {
            return false;
        }

        return hash_equals($pwd, $plain);
    }

    public static function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::SESSION_KEY] = true;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Guard teacher-facing PHP pages. Call before any output.
     */
    public static function requirePage(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!self::isConfigured()) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'NikkQuiz: create config.local.php from config.local.php.example and set site_password (or site_password_hash).';
            exit;
        }

        if (!self::isAuthenticated()) {
            $uri = $_SERVER['REQUEST_URI'] ?? nikkquiz_path('index');
            $redir = self::safeRedirectPath($uri);
            header('Location: login?redirect=' . rawurlencode($redir));
            exit;
        }
    }

    /**
     * Same-origin relative path only (prevents open redirect). Preserves query string from $uri.
     */
    public static function safeRedirectPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);
        if (!is_string($path) || $path === '') {
            return nikkquiz_path('index');
        }
        if ($path[0] !== '/') {
            return nikkquiz_path('index');
        }
        if (strlen($path) >= 2 && $path[1] === '/') {
            return nikkquiz_path('index');
        }

        $out = $path;
        if (is_string($query) && $query !== '') {
            $out .= '?' . $query;
        }

        return $out;
    }

    /**
     * Safe target after login from ?redirect= or form field (path + optional query only).
     */
    public static function safeRedirectFromQuery(string $param): string
    {
        $path = $param;
        if ($path === '') {
            return nikkquiz_path('index');
        }
        if ($path[0] !== '/') {
            return nikkquiz_path('index');
        }
        if (strlen($path) >= 2 && $path[1] === '/') {
            return nikkquiz_path('index');
        }

        return $path;
    }
}
