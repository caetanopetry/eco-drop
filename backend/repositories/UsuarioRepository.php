<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Acesso a dados da tabela `usuarios`.
 * Nenhuma regra de negócio aqui — apenas consultas e comandos SQL (camada de dados).
 */
final class UsuarioRepository
{
    public function buscarPerfil(int $usuarioId): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT id, nome, email, cpf, papel, status, google_sub, criado_em,
                    foto_google, foto_personalizada FROM usuarios WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public function salvarFotoGoogle(int $usuarioId, ?string $foto): void
    {
        // Somente URLs HTTPS vindas do perfil Google verificado.
        if ($foto === null || strlen($foto) > 2048
            || filter_var($foto, FILTER_VALIDATE_URL) === false
            || parse_url($foto, PHP_URL_SCHEME) !== 'https') {
            return;
        }
        $stmt = Database::conexao()->prepare('UPDATE usuarios SET foto_google = :foto WHERE id = :id');
        $stmt->execute(['foto' => $foto, 'id' => $usuarioId]);
    }

    public function salvarFotoPersonalizada(int $usuarioId, string $foto): void
    {
        $stmt = Database::conexao()->prepare('UPDATE usuarios SET foto_personalizada = :foto WHERE id = :id');
        $stmt->execute(['foto' => $foto, 'id' => $usuarioId]);
    }

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
}
