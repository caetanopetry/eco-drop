<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';

/**
 * Acesso centralizado ao banco de dados via PDO.
 *
 * - Prepared statements sempre (nunca concatenar valores em SQL).
 * - Uma única conexão por requisição (singleton).
 * - Exceptions habilitadas para que erros de banco nunca sejam ignorados silenciosamente.
 */
final class Database
{
    private static ?PDO $conexao = null;

    private function __construct()
    {
    }

    public static function conexao(): PDO
    {
        if (self::$conexao instanceof PDO) {
            return self::$conexao;
        }

        $driver = Config::get('database.driver', 'mysql');
        $host = Config::get('database.host', '127.0.0.1');
        $porta = Config::get('database.port', '3306');
        $nomeBanco = Config::get('database.database', 'ecodrop');
        $usuario = Config::get('database.usuario', 'root');
        $senha = Config::get('database.senha', '');
        $charset = Config::get('database.charset', 'utf8mb4');

        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $driver, $host, $porta, $nomeBanco, $charset);

        try {
            self::$conexao = new PDO($dsn, $usuario, $senha, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Nunca expor detalhes de conexão/credenciais na resposta ao cliente.
            error_log('[EcoDrop] Falha ao conectar ao banco: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível conectar ao banco de dados.');
        }

        return self::$conexao;
    }

    /**
     * Executa uma operação dentro de uma transação, com rollback automático em caso de exceção.
     *
     * @template T
     * @param callable(PDO): T $operacao
     * @return T
     */
    public static function transacao(callable $operacao): mixed
    {
        $pdo = self::conexao();
        $pdo->beginTransaction();

        try {
            $resultado = $operacao($pdo);
            $pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
