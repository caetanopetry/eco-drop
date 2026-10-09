<?php

declare(strict_types=1);

/**
 * Leitura e sanitização básica de entrada da requisição.
 *
 * Aceita tanto JSON (application/json) quanto formulários tradicionais (x-www-form-urlencoded),
 * já que o frontend usa fetch() com JSON e algumas páginas podem enviar formulários simples.
 */
final class Request
{
    private static ?array $corpo = null;

    private function __construct()
    {
    }

    private static function corpo(): array
    {
        if (self::$corpo !== null) {
            return self::$corpo;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $bruto = file_get_contents('php://input') ?: '';
            $decodificado = json_decode($bruto, true);
            self::$corpo = is_array($decodificado) ? $decodificado : [];
        } else {
            self::$corpo = $_POST;
        }

        return self::$corpo;
    }

    public static function metodo(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function texto(string $chave, string $padrao = '', bool $aparar = true): string
    {
        $valor = self::corpo()[$chave] ?? $_GET[$chave] ?? $padrao;
        return is_string($valor) ? ($aparar ? trim($valor) : $valor) : $padrao;
    }

}
