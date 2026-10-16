<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

final class AdminEstacoesRepository
{
    public function visaoGeral(): array
    {
        $pdo = Database::conexao();
        $estacoes = $pdo->query("SELECT e.*, COALESCE(d.registros, 0) registros,
            COALESCE(d.validos, 0) validos, COALESCE(d.kg, 0) kg,
            COALESCE(d.litros, 0) litros, COALESCE(d.pontos, 0) pontos, d.ultimo
            FROM estacoes e LEFT JOIN (
                SELECT d.estacao_id, COUNT(*) registros,
                    SUM(d.status = 'valido') validos,
                    SUM(CASE WHEN d.status = 'valido' AND m.unidade = 'kg' THEN d.quantidade ELSE 0 END) kg,
                    SUM(CASE WHEN d.status = 'valido' AND m.unidade = 'l' THEN d.quantidade ELSE 0 END) litros,
                    SUM(d.pontos_gerados) pontos, MAX(d.criado_em) ultimo
                FROM descartes d JOIN materiais m ON m.id = d.material_id GROUP BY d.estacao_id
            ) d ON d.estacao_id = e.id ORDER BY e.nome")->fetchAll();
        $materiais = $pdo->query("SELECT d.estacao_id, m.nome, m.unidade, COUNT(*) registros,
            SUM(d.quantidade) quantidade FROM descartes d JOIN materiais m ON m.id = d.material_id
            WHERE d.status = 'valido' GROUP BY d.estacao_id, m.id, m.nome, m.unidade ORDER BY m.nome")->fetchAll();
        $recentes = $pdo->query("SELECT e.nome estacao, m.nome material, m.unidade, d.quantidade,
            d.status, d.criado_em FROM descartes d JOIN estacoes e ON e.id = d.estacao_id
            JOIN materiais m ON m.id = d.material_id ORDER BY d.criado_em DESC, d.id DESC LIMIT 12")->fetchAll();
        return compact('estacoes', 'materiais', 'recentes');
    }
}
