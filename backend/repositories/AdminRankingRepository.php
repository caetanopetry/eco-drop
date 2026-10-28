<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Ranking administrativo de coletores.
 *
 * A pontuação considera apenas créditos originados por coletas
 * (descarte e bônus de primeiro descarte), os mesmos critérios usados no
 * ranking mensal do aplicativo. Assim, resgates e estornos não interferem
 * na classificação de quem mais pontuou coletando.
 */
final class AdminRankingRepository
{
    public const PERIODOS = [
        'geral' => 'Geral',
        'mes' => 'Mês atual',
    ];

    public function visaoGeral(string $periodo = 'geral'): array
    {
        if (!isset(self::PERIODOS[$periodo])) {
            $periodo = 'geral';
        }

        $pdo = Database::conexao();
        $agora = new DateTimeImmutable($pdo->query('SELECT NOW()')->fetchColumn());

        $filtroMovimentacoes = $periodo === 'mes'
            ? "AND YEAR(mp.criado_em) = YEAR(CURRENT_DATE()) AND MONTH(mp.criado_em) = MONTH(CURRENT_DATE())"
            : '';
        $filtroDescartes = $periodo === 'mes'
            ? "AND YEAR(d.criado_em) = YEAR(CURRENT_DATE()) AND MONTH(d.criado_em) = MONTH(CURRENT_DATE())"
            : '';

        $linhas = $pdo->query(
            "SELECT u.id, u.nome, u.email, u.status,
                    p.pontos,
                    COALESCE(d.descartes, 0) AS descartes,
                    COALESCE(d.kg, 0) AS kg,
                    COALESCE(d.litros, 0) AS litros,
                    d.ultimo_descarte
             FROM usuarios u
             INNER JOIN (
                SELECT mp.usuario_id, SUM(mp.quantidade) AS pontos
                FROM movimentacoes_pontos mp
                WHERE mp.tipo = 'credito'
                  AND mp.origem IN ('descarte', 'bonus_primeiro_descarte')
                  {$filtroMovimentacoes}
                GROUP BY mp.usuario_id
             ) p ON p.usuario_id = u.id
             LEFT JOIN (
                SELECT d.usuario_id, COUNT(*) AS descartes,
                       SUM(CASE WHEN m.unidade = 'kg' THEN d.quantidade ELSE 0 END) AS kg,
                       SUM(CASE WHEN m.unidade = 'l' THEN d.quantidade ELSE 0 END) AS litros,
                       MAX(d.criado_em) AS ultimo_descarte
                FROM descartes d
                INNER JOIN materiais m ON m.id = d.material_id
                WHERE d.status = 'valido'
                  {$filtroDescartes}
                GROUP BY d.usuario_id
             ) d ON d.usuario_id = u.id
             WHERE u.papel = 'usuario'
             ORDER BY p.pontos DESC, descartes DESC, u.nome ASC, u.id ASC"
        )->fetchAll();

        $ranking = [];
        foreach ($linhas as $indice => $linha) {
            $nome = trim((string) $linha['nome']);
            $ranking[] = [
                'posicao' => $indice + 1,
                'id' => (int) $linha['id'],
                'nome' => $nome,
                'email' => (string) $linha['email'],
                'status' => (string) $linha['status'],
                'inicial' => mb_strtoupper(mb_substr($nome !== '' ? $nome : 'U', 0, 1, 'UTF-8'), 'UTF-8'),
                'pontos' => (int) $linha['pontos'],
                'descartes' => (int) $linha['descartes'],
                'kg' => (float) $linha['kg'],
                'litros' => (float) $linha['litros'],
                'ultimo_descarte' => $linha['ultimo_descarte'],
            ];
        }

        $materiais = $pdo->query(
            "SELECT m.nome, m.unidade, COUNT(*) AS descartes,
                    SUM(d.quantidade) AS quantidade,
                    SUM(d.pontos_gerados) AS pontos
             FROM descartes d
             INNER JOIN materiais m ON m.id = d.material_id
             WHERE d.status = 'valido'
               {$filtroDescartes}
             GROUP BY m.id, m.nome, m.unidade
             ORDER BY pontos DESC, m.nome ASC"
        )->fetchAll();

        $recentes = $pdo->query(
            "SELECT d.criado_em, u.nome AS usuario, e.nome AS estacao,
                    m.nome AS material, m.unidade, d.quantidade,
                    d.pontos_gerados
             FROM descartes d
             INNER JOIN usuarios u ON u.id = d.usuario_id
             INNER JOIN estacoes e ON e.id = d.estacao_id
             INNER JOIN materiais m ON m.id = d.material_id
             WHERE d.status = 'valido'
               {$filtroDescartes}
             ORDER BY d.criado_em DESC, d.id DESC
             LIMIT 8"
        )->fetchAll();

        $colecionadores = count($ranking);
        $totalPontos = (int) array_sum(array_column($ranking, 'pontos'));
        $totalDescartes = (int) array_sum(array_column($ranking, 'descartes'));
        $totalKg = (float) array_sum(array_column($ranking, 'kg'));
        $totalLitros = (float) array_sum(array_column($ranking, 'litros'));

        return [
            'periodo' => $periodo,
            'periodo_rotulo' => self::PERIODOS[$periodo],
            'ranking' => $ranking,
            'materiais' => $materiais,
            'recentes' => $recentes,
            'indicadores' => [
                'colecionadores' => $colecionadores,
                'pontos' => $totalPontos,
                'descartes' => $totalDescartes,
                'media' => $colecionadores > 0 ? (int) round($totalPontos / $colecionadores) : 0,
                'kg' => $totalKg,
                'litros' => $totalLitros,
            ],
            'destaque' => $ranking[0] ?? null,
            'atualizado' => $agora->format('d/m/Y H:i:s'),
        ];
    }
}
