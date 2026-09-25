<?php

declare(strict_types=1);

/**
 * Leitor simples da configuracao central do projeto.
 */
final class Config
{
    private static ?array $dados = null;

    private function __construct()
    {
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        $valor = self::dados();

        foreach (explode('.', $chave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $padrao;
            }

            $valor = $valor[$parte];
        }

        return $valor;
    }

    private static function dados(): array
    {
        if (self::$dados !== null) {
            return self::$dados;
        }

        $arquivo = __DIR__ . '/../config/config.php';
        if (!is_file($arquivo)) {
            $arquivo = __DIR__ . '/../config/config.example.php';
        }

        $dados = require $arquivo;
        if (!is_array($dados)) {
            throw new RuntimeException('Arquivo de configuracao invalido.');
        }

        if (!empty($dados['app']['timezone'])) {
            date_default_timezone_set((string) $dados['app']['timezone']);
        }

        self::$dados = $dados;
        return self::$dados;
    }
}
