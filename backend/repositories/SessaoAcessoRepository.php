<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

final class SessaoAcessoRepository
{
    public function registrar(int $usuarioId, string $sessao): void
    {
        $stmt = Database::conexao()->prepare(
            'INSERT INTO sessoes_acesso (usuario_id, token_hash) VALUES (:usuario, :token)'
        );
        $stmt->execute(['usuario' => $usuarioId, 'token' => hash('sha256', $sessao)]);
    }

    public function atualizar(int $usuarioId, string $sessao): void
    {
        $stmt = Database::conexao()->prepare(
            'UPDATE sessoes_acesso SET ultima_atividade_em = NOW()
             WHERE token_hash = :token AND usuario_id = :usuario AND encerrado_em IS NULL'
        );
        $stmt->execute(['usuario' => $usuarioId, 'token' => hash('sha256', $sessao)]);
    }

    public function encerrar(string $sessao): void
    {
        $stmt = Database::conexao()->prepare(
            'UPDATE sessoes_acesso SET encerrado_em = NOW()
             WHERE token_hash = :token AND encerrado_em IS NULL'
        );
        $stmt->execute(['token' => hash('sha256', $sessao)]);
    }
}
