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

    public static function texto(string $chave, string $padrao = ''): string
    {
        $valor = self::corpo()[$chave] ?? $_GET[$chave] ?? $padrao;
        return is_string($valor) ? trim($valor) : $padrao;
    }

    public static function numero(string $chave, float $padrao = 0.0): float
    {
        $valor = self::corpo()[$chave] ?? $_GET[$chave] ?? $padrao;

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        return $padrao;
    }

    public static function inteiro(string $chave, int $padrao = 0): int
    {
        $valor = self::corpo()[$chave] ?? $_GET[$chave] ?? $padrao;
        return is_numeric($valor) ? (int) $valor : $padrao;
    }

    public static function todos(): array
    {
        return self::corpo();
    }
}
