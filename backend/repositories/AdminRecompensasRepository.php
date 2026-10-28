<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Cadastro e consulta das recompensas exibidas aos usuários.
 *
 * Recompensas com status "ativo" entram imediatamente no catálogo do app
 * (EcoDropRepository::recompensasAtivas). A pausa apenas oculta o item sem
 * apagar o histórico de resgates e vouchers.
 */
final class AdminRecompensasRepository
{
    public const STATUS = [
        'ativo' => 'Ativa no app',
        'inativo' => 'Pausada',
    ];

    public function listar(): array
    {
        return Database::conexao()->query(
            "SELECT r.*, p.nome AS parceiro,
                    (SELECT COUNT(*) FROM resgates rg WHERE rg.recompensa_id = r.id) AS resgates
             FROM recompensas r
             LEFT JOIN parceiros p ON p.id = r.parceiro_id
             ORDER BY (r.status = 'ativo') DESC, r.criado_em DESC, r.id DESC"
        )->fetchAll();
    }

    public function parceiros(): array
    {
        return Database::conexao()
            ->query("SELECT id, nome FROM parceiros WHERE status = 'ativo' ORDER BY nome")
            ->fetchAll();
    }

    public function criar(array $entrada): int
    {
        $nome = $this->texto($entrada, 'nome');
        $descricao = $this->texto($entrada, 'descricao');

        if (mb_strlen($nome, 'UTF-8') < 3 || mb_strlen($nome, 'UTF-8') > 150) {
            throw new InvalidArgumentException('Informe um nome entre 3 e 150 caracteres.');
        }

        if (mb_strlen($descricao, 'UTF-8') < 3 || mb_strlen($descricao, 'UTF-8') > 2000) {
            throw new InvalidArgumentException('Informe uma descrição entre 3 e 2.000 caracteres.');
        }

        $custo = filter_var(
            $entrada['custo_pontos'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 4294967295]]
        );
        if ($custo === false) {
            throw new InvalidArgumentException('Informe um custo em pontos inteiro e maior que zero.');
        }

        $estoque = $this->inteiroOpcional($entrada['estoque'] ?? null, 0, 2147483647, 'estoque');
        $validade = $this->inteiroOpcional($entrada['validade_dias'] ?? null, 1, 3650, 'validade');

        $parceiroId = null;
        $parceiroBruto = trim((string) ($entrada['parceiro_id'] ?? ''));
        if ($parceiroBruto !== '') {
            $parceiroId = filter_var(
                $parceiroBruto,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($parceiroId === false) {
                throw new InvalidArgumentException('Selecione um parceiro válido.');
            }

            $stmt = Database::conexao()->prepare("SELECT id FROM parceiros WHERE id = :id AND status = 'ativo'");
            $stmt->execute(['id' => $parceiroId]);
            if ($stmt->fetchColumn() === false) {
                throw new InvalidArgumentException('O parceiro selecionado não está ativo.');
            }
        }

        $status = is_string($entrada['status'] ?? null) && isset(self::STATUS[$entrada['status']])
            ? $entrada['status']
            : 'ativo';

        try {
            $stmt = Database::conexao()->prepare(
                "INSERT INTO recompensas
                    (parceiro_id, nome, descricao, custo_pontos, estoque, validade_dias, status)
                 VALUES
                    (:parceiro_id, :nome, :descricao, :custo_pontos, :estoque, :validade_dias, :status)"
            );
            $stmt->execute([
                'parceiro_id' => $parceiroId,
                'nome' => $nome,
                'descricao' => $descricao,
                'custo_pontos' => $custo,
                'estoque' => $estoque,
                'validade_dias' => $validade,
                'status' => $status,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new InvalidArgumentException('Já existe uma recompensa com esse nome.');
            }
            throw $e;
        }

        return (int) Database::conexao()->lastInsertId();
    }

    public function alternarStatus(int $id): string
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Recompensa inválida.');
        }

        return Database::transacao(static function (PDO $pdo) use ($id): string {
            $stmt = $pdo->prepare('SELECT status FROM recompensas WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $status = $stmt->fetchColumn();

            if ($status === false) {
                throw new InvalidArgumentException('Recompensa não encontrada.');
            }

            $novoStatus = $status === 'ativo' ? 'inativo' : 'ativo';
            $stmt = $pdo->prepare('UPDATE recompensas SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $novoStatus, 'id' => $id]);

            return $novoStatus;
        });
    }

    private function texto(array $entrada, string $campo): string
    {
        $valor = $entrada[$campo] ?? null;
        if (!is_string($valor)) {
            return '';
        }

        return trim($valor);
    }

    private function inteiroOpcional(mixed $valor, int $minimo, int $maximo, string $campo): ?int
    {
        $texto = is_string($valor) ? trim($valor) : '';
        if ($texto === '') {
            return null;
        }

        $inteiro = filter_var(
            $texto,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => $minimo, 'max_range' => $maximo]]
        );
        if ($inteiro === false) {
            $rotulo = $campo === 'estoque' ? 'estoque' : 'validade em dias';
            throw new InvalidArgumentException("Informe um {$rotulo} válido.");
        }

        return $inteiro;
    }
}
