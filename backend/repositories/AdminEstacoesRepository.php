<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

final class AdminEstacoesRepository
{
    public const STATUS = [
        'ativa' => 'Ativa',
        'inativa' => 'Inativa',
        'manutencao' => 'Em manutenção',
    ];

    public function visaoGeral(): array
    {
        $pdo = Database::conexao();
        $estacoes = $pdo->query(
            "SELECT e.*, COALESCE(d.registros, 0) AS registros,
                    COALESCE(d.validos, 0) AS validos,
                    COALESCE(d.kg, 0) AS kg,
                    COALESCE(d.litros, 0) AS litros,
                    COALESCE(d.pontos, 0) AS pontos, d.ultimo
             FROM estacoes e
             LEFT JOIN (
                 SELECT d.estacao_id, COUNT(*) AS registros,
                        SUM(d.status = 'valido') AS validos,
                        SUM(CASE WHEN d.status = 'valido' AND m.unidade = 'kg' THEN d.quantidade ELSE 0 END) AS kg,
                        SUM(CASE WHEN d.status = 'valido' AND m.unidade = 'l' THEN d.quantidade ELSE 0 END) AS litros,
                        SUM(d.pontos_gerados) AS pontos,
                        MAX(d.criado_em) AS ultimo
                 FROM descartes d
                 INNER JOIN materiais m ON m.id = d.material_id
                 GROUP BY d.estacao_id
             ) d ON d.estacao_id = e.id
             ORDER BY (e.status = 'ativa') DESC, e.codigo"
        )->fetchAll();

        $materiais = $pdo->query(
            "SELECT d.estacao_id, m.nome, m.unidade, COUNT(*) AS registros,
                    SUM(d.quantidade) AS quantidade
             FROM descartes d
             INNER JOIN materiais m ON m.id = d.material_id
             WHERE d.status = 'valido'
             GROUP BY d.estacao_id, m.id, m.nome, m.unidade
             ORDER BY m.nome"
        )->fetchAll();

        $recentes = $pdo->query(
            "SELECT e.nome AS estacao, m.nome AS material, m.unidade,
                    d.quantidade, d.status, d.criado_em
             FROM descartes d
             INNER JOIN estacoes e ON e.id = d.estacao_id
             INNER JOIN materiais m ON m.id = d.material_id
             ORDER BY d.criado_em DESC, d.id DESC
             LIMIT 12"
        )->fetchAll();

        return compact('estacoes', 'materiais', 'recentes');
    }

    /** @return array{id: int, codigo: string} */
    public function criar(array $entrada): array
    {
        $nome = is_string($entrada['nome'] ?? null) ? trim($entrada['nome']) : '';
        $endereco = is_string($entrada['endereco'] ?? null) ? trim($entrada['endereco']) : '';
        $status = is_string($entrada['status'] ?? null) && isset(self::STATUS[$entrada['status']])
            ? $entrada['status']
            : 'ativa';

        if (mb_strlen($nome, 'UTF-8') < 3 || mb_strlen($nome, 'UTF-8') > 150) {
            throw new InvalidArgumentException('Informe um nome entre 3 e 150 caracteres.');
        }

        if (mb_strlen($endereco, 'UTF-8') > 255) {
            throw new InvalidArgumentException('O endereço deve ter no máximo 255 caracteres.');
        }

        try {
            return Database::transacao(function (PDO $pdo) use ($nome, $endereco, $status): array {
                $codigo = $this->gerarCodigo($pdo);
                $stmt = $pdo->prepare(
                    'INSERT INTO estacoes (codigo, nome, endereco, status)
                     VALUES (:codigo, :nome, :endereco, :status)'
                );
                $stmt->execute([
                    'codigo' => $codigo,
                    'nome' => $nome,
                    'endereco' => $endereco !== '' ? $endereco : null,
                    'status' => $status,
                ]);

                return ['id' => (int) $pdo->lastInsertId(), 'codigo' => $codigo];
            });
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new InvalidArgumentException('Não foi possível gerar um código exclusivo para a estação. Tente novamente.');
            }
            throw $e;
        }
    }

    public function alternarStatus(int $id): string
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Estação inválida.');
        }

        return Database::transacao(static function (PDO $pdo) use ($id): string {
            $stmt = $pdo->prepare('SELECT status FROM estacoes WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $status = $stmt->fetchColumn();

            if ($status === false) {
                throw new InvalidArgumentException('Estação não encontrada.');
            }

            $novoStatus = $status === 'ativa' ? 'inativa' : 'ativa';
            $stmt = $pdo->prepare('UPDATE estacoes SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $novoStatus, 'id' => $id]);

            return $novoStatus;
        });
    }

    private function gerarCodigo(?PDO $pdo = null): string
    {
        $pdo ??= Database::conexao();
        $codigos = $pdo->query('SELECT codigo FROM estacoes')->fetchAll(PDO::FETCH_COLUMN);

        $maior = 0;
        foreach ($codigos as $codigo) {
            if (preg_match('/^ECO-(\d+)$/i', (string) $codigo, $encontrado) === 1) {
                $maior = max($maior, (int) $encontrado[1]);
            }
        }

        return 'ECO-' . str_pad((string) ($maior + 1), 3, '0', STR_PAD_LEFT);
    }
}
