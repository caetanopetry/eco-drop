<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

final class AdminParceirosRepository
{
    public const STATUS = [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
    ];

    public function listar(): array
    {
        return Database::conexao()->query(
            "SELECT id, nome, descricao, contato, status, criado_em
             FROM parceiros
             ORDER BY (status = 'ativo') DESC, nome"
        )->fetchAll();
    }

    public function criar(array $entrada): int
    {
        $nome = $this->texto($entrada, 'nome');
        $descricao = $this->texto($entrada, 'descricao');
        $contato = $this->texto($entrada, 'contato');
        $status = $entrada['status'] ?? 'ativo';
        if (!is_string($status) || !isset(self::STATUS[$status])) {
            throw new InvalidArgumentException('Selecione uma disponibilidade válida para o parceiro.');
        }

        if (mb_strlen($nome, 'UTF-8') < 2 || mb_strlen($nome, 'UTF-8') > 150) {
            throw new InvalidArgumentException('Informe um nome entre 2 e 150 caracteres.');
        }

        if (mb_strlen($descricao, 'UTF-8') > 255) {
            throw new InvalidArgumentException('A descrição deve ter no máximo 255 caracteres.');
        }

        if (mb_strlen($contato, 'UTF-8') > 150) {
            throw new InvalidArgumentException('O contato deve ter no máximo 150 caracteres.');
        }

        try {
            $stmt = Database::conexao()->prepare(
                'INSERT INTO parceiros (nome, descricao, contato, status)
                 VALUES (:nome, :descricao, :contato, :status)'
            );
            $stmt->execute([
                'nome' => $nome,
                'descricao' => $descricao !== '' ? $descricao : null,
                'contato' => $contato !== '' ? $contato : null,
                'status' => $status,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new InvalidArgumentException('Já existe um parceiro com esse nome.');
            }
            throw $e;
        }

        return (int) Database::conexao()->lastInsertId();
    }

    private function texto(array $entrada, string $campo): string
    {
        return is_string($entrada[$campo] ?? null) ? trim($entrada[$campo]) : '';
    }
}
