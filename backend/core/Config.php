<?php

declare(strict_types=1);

require_once __DIR__ . '/Env.php';

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

        Env::carregar();

        $arquivo = __DIR__ . '/../config/config.php';
        if (!is_file($arquivo)) {
            $arquivo = __DIR__ . '/../config/config.example.php';
        }

        $dados = require $arquivo;
        if (!is_array($dados)) {
            throw new RuntimeException('Arquivo de configuracao invalido.');
        }

        $variavelObrigatoria = static function (string $nome, bool $permitirVazio = false): string {
            $valor = $_ENV[$nome] ?? getenv($nome);
            if (!is_string($valor) || (!$permitirVazio && trim($valor) === '')) {
                throw new RuntimeException('Variavel de ambiente obrigatoria ausente: ' . $nome . '.');
            }

            return $valor;
        };

        $dados['google'] = [
            'client_id' => (string) ($_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? ''),
            'client_secret' => (string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? ''),
            'redirect_uri' => (string) ($_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?? ''),
            'scopes' => array_values(array_filter(array_map('trim', preg_split('/\s+/', (string) ($_ENV['GOOGLE_SCOPES'] ?? getenv('GOOGLE_SCOPES') ?? 'openid email profile'))), static fn (string $scope): bool => $scope !== '')),
        ];

        $dados['database'] = [
            'driver' => 'mysql',
            'host' => $variavelObrigatoria('DB_HOST'),
            'port' => $variavelObrigatoria('DB_PORT'),
            'database' => $variavelObrigatoria('DB_NAME'),
            'usuario' => $variavelObrigatoria('DB_USER'),
            'senha' => $variavelObrigatoria('DB_PASS', true),
            'charset' => 'utf8mb4',
        ];

        if (!empty($dados['app']['timezone'])) {
            date_default_timezone_set((string) $dados['app']['timezone']);
        }

        self::$dados = $dados;
        return self::$dados;
    }
}
