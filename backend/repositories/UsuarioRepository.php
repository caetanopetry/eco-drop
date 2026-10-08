<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Acesso a dados da tabela `usuarios`.
 * Nenhuma regra de negócio aqui — apenas consultas e comandos SQL (camada de dados).
 */
final class UsuarioRepository
{
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT id, nome, email, cpf, senha_hash, papel, status, google_sub
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function buscarPorGoogleSub(string $googleSub): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT id, nome, email, papel, status, google_sub
             FROM usuarios
             WHERE google_sub = :google_sub
             LIMIT 1'
        );
        $stmt->execute(['google_sub' => $googleSub]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function buscarPorCpf(string $cpf): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT id FROM usuarios WHERE cpf = :cpf LIMIT 1'
        );
        $stmt->execute(['cpf' => $cpf]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function criar(
        string $nome,
        string $email,
        string $cpf,
        string $senhaHash,
        string $papel = 'usuario',
        ?string $googleSub = null
    ): int
    {
        $stmt = Database::conexao()->prepare(
            'INSERT INTO usuarios (nome, email, cpf, senha_hash, papel, status, google_sub, criado_em)
             VALUES (:nome, :email, :cpf, :senha_hash, :papel, "ativo", :google_sub, NOW())'
        );

        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'cpf' => $cpf,
            'senha_hash' => $senhaHash,
            'papel' => $papel,
            'google_sub' => $googleSub,
        ]);

        return (int) Database::conexao()->lastInsertId();
    }

    public function vincularGoogle(int $usuarioId, string $googleSub): bool
    {
        $stmt = Database::conexao()->prepare(
            'UPDATE usuarios
             SET google_sub = :google_sub
             WHERE id = :id AND google_sub IS NULL'
        );
        $stmt->execute([
            'google_sub' => $googleSub,
            'id' => $usuarioId,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function buscarRecuperacaoValida(string $tokenHash): bool
    {
        $stmt = Database::conexao()->prepare(
            'SELECT id
             FROM recuperacoes_acesso
             WHERE token_hash = :token_hash
               AND utilizado_em IS NULL
               AND expira_em > UTC_TIMESTAMP()
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);

        return $stmt->fetch() !== false;
    }

    public function criarRecuperacao(int $usuarioId, string $tokenHash, string $expiraEm): void
    {
        Database::transacao(static function (PDO $pdo) use ($usuarioId, $tokenHash, $expiraEm): void {
            $bloquearUsuario = $pdo->prepare(
                'SELECT id FROM usuarios WHERE id = :usuario_id FOR UPDATE'
            );
            $bloquearUsuario->execute(['usuario_id' => $usuarioId]);
            if ($bloquearUsuario->fetch() === false) {
                throw new RuntimeException('A conta para recuperação não está disponível.');
            }

            $invalidar = $pdo->prepare(
                'UPDATE recuperacoes_acesso
                 SET utilizado_em = UTC_TIMESTAMP()
                 WHERE usuario_id = :usuario_id AND utilizado_em IS NULL'
            );
            $invalidar->execute(['usuario_id' => $usuarioId]);

            $inserir = $pdo->prepare(
                'INSERT INTO recuperacoes_acesso (usuario_id, token_hash, expira_em, criado_em)
                 VALUES (:usuario_id, :token_hash, :expira_em, UTC_TIMESTAMP())'
            );
            $inserir->execute([
                'usuario_id' => $usuarioId,
                'token_hash' => $tokenHash,
                'expira_em' => $expiraEm,
            ]);
        });
    }

    public function invalidarRecuperacoesPendentes(int $usuarioId): void
    {
        $stmt = Database::conexao()->prepare(
            'UPDATE recuperacoes_acesso
             SET utilizado_em = UTC_TIMESTAMP()
             WHERE usuario_id = :usuario_id AND utilizado_em IS NULL'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
    }

    public function redefinirSenhaComToken(string $tokenHash, string $senhaHash): bool
    {
        return Database::transacao(static function (PDO $pdo) use ($tokenHash, $senhaHash): bool {
            $buscar = $pdo->prepare(
                'SELECT id, usuario_id
                 FROM recuperacoes_acesso
                 WHERE token_hash = :token_hash
                   AND utilizado_em IS NULL
                   AND expira_em > UTC_TIMESTAMP()
                 LIMIT 1
                 FOR UPDATE'
            );
            $buscar->execute(['token_hash' => $tokenHash]);
            $recuperacao = $buscar->fetch();

            if ($recuperacao === false) {
                return false;
            }

            $atualizarSenha = $pdo->prepare(
                'UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :usuario_id'
            );
            $atualizarSenha->execute([
                'senha_hash' => $senhaHash,
                'usuario_id' => $recuperacao['usuario_id'],
            ]);

            if ($atualizarSenha->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível atualizar a senha da conta.');
            }

            $invalidar = $pdo->prepare(
                'UPDATE recuperacoes_acesso
                 SET utilizado_em = UTC_TIMESTAMP()
                 WHERE usuario_id = :usuario_id AND utilizado_em IS NULL'
            );
            $invalidar->execute(['usuario_id' => $recuperacao['usuario_id']]);

            return true;
        });
    }
}
