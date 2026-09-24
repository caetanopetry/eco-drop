<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';

/**
 * Gera caminhos respeitando a subpasta configurada em app.url_base.
 */
final class Url
{
    private function __construct()
    {
    }

    public static function para(string $caminho = '/'): string
    {
        $base = self::basePath();
        $caminho = '/' . ltrim($caminho, '/');

        if ($caminho === '/') {
            return $base === '' ? '/' : $base . '/';
        }

        return $base . $caminho;
    }

    public static function asset(string $caminho): string
    {
        return self::para($caminho);
    }

    public static function escapar(string $caminho = '/'): string
    {
        return htmlspecialchars(self::para($caminho), ENT_QUOTES, 'UTF-8');
    }

    private static function basePath(): string
    {
        $urlBase = rtrim((string) Config::get('app.url_base', ''), '/');
        $path = parse_url($urlBase, PHP_URL_PATH);

        if (!is_string($path) || $path === '/') {
            return '';
        }

        return rtrim($path, '/');
    }
}
