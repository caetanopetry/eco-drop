<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Acesso a dados de `movimentacoes_pontos`.
 *
 * Decisão de arquitetura (docs/decisoes/banco-de-dados.md): não existe uma coluna
 * de saldo isolada. O saldo é sempre calculado a partir da soma das movimentações,
 * o que evita que um valor em cache fique inconsistente com o extrato (RNF006, RNF015).
 */
final class PontosRepository
{
    public function saldoDoUsuario(int $usuarioId): int
    {
        $stmt = Database::conexao()->prepare(
            "SELECT COALESCE(SUM(
                CASE WHEN tipo = 'credito' THEN quantidade ELSE -quantidade END
             ), 0) AS saldo
             FROM movimentacoes_pontos
             WHERE usuario_id = :usuario_id"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    public function totalDeMovimentacoes(int $usuarioId): int
    {
        $stmt = Database::conexao()->prepare(
            'SELECT COUNT(*) FROM movimentacoes_pontos WHERE usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }
}
