<?php

declare(strict_types=1);

use Dotenv\Dotenv;

/**
 * Carrega variáveis de ambiente do projeto a partir do .env localizado na raiz.
 */
final class Env
{
    private static bool $carregado = false;

    private function __construct()
    {
    }

    public static function carregar(?string $caminho = null): void
    {
        if (self::$carregado) {
            return;
        }

        $base = $caminho ?? dirname(__DIR__, 2);
        $arquivo = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';

        $autoload = $base . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }

        if (!is_file($arquivo) || !class_exists(Dotenv::class)) {
            self::$carregado = true;
            return;
        }

        Dotenv::createImmutable($base)->safeLoad();
        self::$carregado = true;
    }
}
