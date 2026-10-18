<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Controle simples de tentativas (rate limiting) para login e recuperação de
 * senha. Protege contra força bruta e contra abuso no envio de e-mails.
 *
 * O identificador (normalmente e-mail + IP) nunca é gravado em claro: guardamos
 * apenas o hash SHA-256, tanto para privacidade quanto para permitir índice único.
 */
final class RateLimiter
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::conexao();
    }

    /**
     * Verifica se já houve tentativas demais para o identificador na janela.
     * Aproveita para limpar registros antigos e manter a tabela pequena.
     */
    public function demasiadasTentativas(string $escopo, string $identificador, int $maximo, int $janelaSegundos): bool
    {
        $this->limparAntigos($janelaSegundos);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM tentativas_acesso
              WHERE escopo = :escopo AND identificador = :identificador AND criado_em >= :desde'
        );
        $stmt->execute([
            'escopo' => $escopo,
            'identificador' => $this->hash($identificador),
            'desde' => date('Y-m-d H:i:s', time() - $janelaSegundos),
        ]);

        return (int) $stmt->fetchColumn() >= $maximo;
    }

    /** Registra uma tentativa malsucedida. */
    public function registrar(string $escopo, string $identificador): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tentativas_acesso (escopo, identificador, ip) VALUES (:escopo, :identificador, :ip)'
        );
        $stmt->execute([
            'escopo' => $escopo,
            'identificador' => $this->hash($identificador),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    /** Zera o contador (ex.: após um login bem-sucedido). */
    public function limpar(string $escopo, string $identificador): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM tentativas_acesso WHERE escopo = :escopo AND identificador = :identificador'
        );
        $stmt->execute(['escopo' => $escopo, 'identificador' => $this->hash($identificador)]);
    }

    private function limparAntigos(int $janelaSegundos): void
    {
        $limite = date('Y-m-d H:i:s', time() - max($janelaSegundos * 4, 3600));
        $stmt = $this->pdo->prepare('DELETE FROM tentativas_acesso WHERE criado_em < :limite');
        $stmt->execute(['limite' => $limite]);
    }

    private function hash(string $identificador): string
    {
        return hash('sha256', $identificador);
    }
}
