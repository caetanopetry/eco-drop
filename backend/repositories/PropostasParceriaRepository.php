<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

/** Recebimento e avaliação compartilháveis com o futuro formulário público. */
final class PropostasParceriaRepository
{
    public const STATUS = ['nova'=>'Nova', 'em_analise'=>'Em análise', 'aprovada'=>'Aprovada', 'recusada'=>'Recusada'];
    public const CAMPOS = ['organizacao'=>150, 'responsavel'=>150, 'email'=>150, 'telefone'=>40,
        'cidade'=>150, 'segmento'=>100, 'tipo'=>100, 'proposta'=>5000];

    public function listar(): array
    {
        return Database::conexao()->query('SELECT p.*, u.nome avaliador FROM propostas_parceria p
            LEFT JOIN usuarios u ON u.id=p.avaliado_por ORDER BY p.criado_em DESC, p.id DESC')->fetchAll();
    }

    public function receber(array $entrada): int
    {
        $dados=[];
        foreach (self::CAMPOS as $campo=>$limite) {
            $valor=$entrada[$campo] ?? null;
            if (!is_string($valor) || trim($valor)==='' || mb_strlen(trim($valor))>$limite) {
                throw new InvalidArgumentException('Preencha todos os campos e respeite os limites indicados.');
            }
            $dados[$campo]=trim($valor);
        }
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe um e-mail válido.');
        $stmt=Database::conexao()->prepare('INSERT INTO propostas_parceria
            (organizacao,responsavel,email,telefone,cidade,segmento,tipo,proposta)
            VALUES (:organizacao,:responsavel,:email,:telefone,:cidade,:segmento,:tipo,:proposta)');
        $stmt->execute($dados);
        return (int) Database::conexao()->lastInsertId();
    }

    public function avaliar(int $id, string $status, string $notas, int $admin): void
    {
        if (!isset(self::STATUS[$status]) || mb_strlen($notas)>5000 || $id<1) throw new InvalidArgumentException('Avaliação inválida. Confira o status e o limite de 5.000 caracteres.');
        $stmt=Database::conexao()->prepare('UPDATE propostas_parceria SET status=?, notas=?, avaliado_por=?, avaliado_em=CURRENT_TIMESTAMP WHERE id=?');
        $stmt->execute([$status,trim($notas),$admin,$id]);
        if (!$stmt->rowCount()) {
            $existe=Database::conexao()->prepare('SELECT id FROM propostas_parceria WHERE id=?');
            $existe->execute([$id]);
            if (!$existe->fetchColumn()) throw new InvalidArgumentException('Proposta não encontrada.');
        }
    }
}
