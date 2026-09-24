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
            'SELECT id, nome, email, cpf, senha_hash, papel, status
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
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

    public function buscarPorId(int $id): ?array
    {
        $stmt = Database::conexao()->prepare(
            'SELECT id, nome, email, cpf, papel, status, criado_em
             FROM usuarios
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function criar(string $nome, string $email, string $cpf, string $senhaHash, string $papel = 'usuario'): int
    {
        $stmt = Database::conexao()->prepare(
            'INSERT INTO usuarios (nome, email, cpf, senha_hash, papel, status, criado_em)
             VALUES (:nome, :email, :cpf, :senha_hash, :papel, "ativo", NOW())'
        );

        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'cpf' => $cpf,
            'senha_hash' => $senhaHash,
            'papel' => $papel,
        ]);

        return (int) Database::conexao()->lastInsertId();
    }
}
