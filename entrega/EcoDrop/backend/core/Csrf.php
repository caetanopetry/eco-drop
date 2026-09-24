<?php

declare(strict_types=1);

/**
 * Proteção CSRF simples: um token por sessão, exigido em toda requisição
 * que altera estado (POST/PUT/DELETE) feita pelas páginas do EcoDrop.
 */
final class Csrf
{
    private const CHAVE_SESSAO = '_csrf_token';

    private function __construct()
    {
    }

    public static function token(): string
    {
        if (empty($_SESSION[self::CHAVE_SESSAO])) {
            $_SESSION[self::CHAVE_SESSAO] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CHAVE_SESSAO];
    }

    public static function validar(?string $tokenRecebido): bool
    {
        if (empty($_SESSION[self::CHAVE_SESSAO]) || empty($tokenRecebido)) {
            return false;
        }

        return hash_equals($_SESSION[self::CHAVE_SESSAO], $tokenRecebido);
    }

    /**
     * Campo hidden pronto para uso em formulários HTML tradicionais.
     */
    public static function campoHtml(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
