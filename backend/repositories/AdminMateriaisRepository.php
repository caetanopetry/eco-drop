<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Painel administrativo de materiais.
 *
 * Contabiliza apenas descartes válidos nos totais de depósitos, volume e
 * pontos. A categoria é derivada do nome/slug do material para permitir a
 * separação visual (metal, plástico, papel, vidro, orgânico, óleo etc.) sem
 * alterar a estrutura atual da tabela `materiais`.
 */
final class AdminMateriaisRepository
{
    public const PERIODOS = [
        'geral' => 'Geral',
        'mes' => 'Mês atual',
    ];

    public const CATEGORIAS = [
        'metal' => 'Metal',
        'plastico' => 'Plástico',
        'papel' => 'Papel',
        'vidro' => 'Vidro',
        'organico' => 'Orgânico',
        'oleo' => 'Óleo',
        'eletronico' => 'Eletrônicos',
        'outros' => 'Outros',
    ];

    public function visaoGeral(string $periodo = 'geral'): array
    {
        if (!isset(self::PERIODOS[$periodo])) {
            $periodo = 'geral';
        }

        $pdo = Database::conexao();
        $agora = new DateTimeImmutable($pdo->query('SELECT NOW()')->fetchColumn());
        $filtro = $periodo === 'mes'
            ? "AND YEAR(d.criado_em) = YEAR(CURRENT_DATE()) AND MONTH(d.criado_em) = MONTH(CURRENT_DATE())"
            : '';

        $linhas = $pdo->query(
            "SELECT m.id, m.nome, m.slug, m.unidade, m.quantidade_minima,
                    m.pontos_por_unidade, m.status,
                    COALESCE(t.depositos, 0) AS depositos,
                    COALESCE(t.quantidade, 0) AS quantidade,
                    COALESCE(t.pontos, 0) AS pontos,
                    COALESCE(t.invalidos, 0) AS invalidos,
                    t.ultimo
             FROM materiais m
             LEFT JOIN (
                SELECT d.material_id,
                       SUM(CASE WHEN d.status = 'valido' THEN 1 ELSE 0 END) AS depositos,
                       SUM(CASE WHEN d.status = 'valido' THEN d.quantidade ELSE 0 END) AS quantidade,
                       SUM(CASE WHEN d.status = 'valido' THEN d.pontos_gerados ELSE 0 END) AS pontos,
                       SUM(CASE WHEN d.status = 'invalido' THEN 1 ELSE 0 END) AS invalidos,
                       MAX(CASE WHEN d.status = 'valido' THEN d.criado_em END) AS ultimo
                FROM descartes d
                WHERE 1 = 1
                  {$filtro}
                GROUP BY d.material_id
             ) t ON t.material_id = m.id
             ORDER BY depositos DESC, m.nome ASC"
        )->fetchAll();

        $materiais = [];
        foreach ($linhas as $linha) {
            $nome = (string) $linha['nome'];
            $slug = (string) $linha['slug'];
            $categoria = self::categoriaDe($nome, $slug);
            $materiais[] = [
                'id' => (int) $linha['id'],
                'nome' => $nome,
                'slug' => $slug,
                'unidade' => (string) $linha['unidade'],
                'quantidade_minima' => (float) $linha['quantidade_minima'],
                'pontos_por_unidade' => $linha['pontos_por_unidade'] === null ? null : (float) $linha['pontos_por_unidade'],
                'status' => (string) $linha['status'],
                'categoria' => $categoria,
                'categoria_nome' => self::CATEGORIAS[$categoria],
                'depositos' => (int) $linha['depositos'],
                'quantidade' => (float) $linha['quantidade'],
                'pontos' => (int) $linha['pontos'],
                'invalidos' => (int) $linha['invalidos'],
                'ultimo' => $linha['ultimo'],
                'percentual' => 0.0,
            ];
        }

        usort($materiais, static function (array $a, array $b): int {
            return [$b['depositos'], $b['pontos'], $a['nome']] <=> [$a['depositos'], $a['pontos'], $b['nome']];
        });

        $totalDepositos = (int) array_sum(array_column($materiais, 'depositos'));
        foreach ($materiais as &$material) {
            $material['percentual'] = $totalDepositos > 0
                ? round(($material['depositos'] / $totalDepositos) * 100, 1)
                : 0.0;
        }
        unset($material);

        $categorias = [];
        foreach (self::CATEGORIAS as $chave => $rotulo) {
            $categorias[$chave] = [
                'chave' => $chave,
                'nome' => $rotulo,
                'materiais' => 0,
                'coletados' => 0,
                'depositos' => 0,
                'kg' => 0.0,
                'litros' => 0.0,
                'pontos' => 0,
                'percentual' => 0.0,
            ];
        }

        foreach ($materiais as $material) {
            $categoria = &$categorias[$material['categoria']];
            $categoria['materiais']++;
            $categoria['coletados'] += $material['depositos'] > 0 ? 1 : 0;
            $categoria['depositos'] += $material['depositos'];
            $categoria['pontos'] += $material['pontos'];
            if ($material['unidade'] === 'l') {
                $categoria['litros'] += $material['quantidade'];
            } else {
                $categoria['kg'] += $material['quantidade'];
            }
            unset($categoria);
        }

        foreach ($categorias as &$categoria) {
            $categoria['percentual'] = $totalDepositos > 0
                ? round(($categoria['depositos'] / $totalDepositos) * 100, 1)
                : 0.0;
        }
        unset($categoria);

        // Exibe apenas categorias com pelo menos um material cadastrado.
        $categorias = array_values(array_filter(
            $categorias,
            static fn(array $categoria): bool => $categoria['materiais'] > 0
        ));

        $recentes = $pdo->query(
            "SELECT d.criado_em, u.nome AS usuario, e.nome AS estacao,
                    m.nome AS material, m.unidade, d.quantidade,
                    d.pontos_gerados
             FROM descartes d
             INNER JOIN usuarios u ON u.id = d.usuario_id
             INNER JOIN estacoes e ON e.id = d.estacao_id
             INNER JOIN materiais m ON m.id = d.material_id
             WHERE d.status = 'valido'
               {$filtro}
             ORDER BY d.criado_em DESC, d.id DESC
             LIMIT 8"
        )->fetchAll();

        $coletados = array_values(array_filter(
            $materiais,
            static fn(array $material): bool => $material['depositos'] > 0
        ));

        return [
            'periodo' => $periodo,
            'periodo_rotulo' => self::PERIODOS[$periodo],
            'materiais' => $materiais,
            'categorias' => $categorias,
            'recentes' => $recentes,
            'indicadores' => [
                'tipos_cadastrados' => count($materiais),
                'tipos_coletados' => count($coletados),
                'depositos' => $totalDepositos,
                'kg' => (float) array_sum(array_map(
                    static fn(array $material): float => $material['unidade'] === 'kg' ? $material['quantidade'] : 0.0,
                    $materiais
                )),
                'litros' => (float) array_sum(array_map(
                    static fn(array $material): float => $material['unidade'] === 'l' ? $material['quantidade'] : 0.0,
                    $materiais
                )),
                'pontos' => (int) array_sum(array_column($materiais, 'pontos')),
                'invalidos' => (int) array_sum(array_column($materiais, 'invalidos')),
            ],
            'destaque' => $coletados[0] ?? null,
            'atualizado' => $agora->format('d/m/Y H:i:s'),
        ];
    }

    public static function categoriaDe(string $nome, string $slug): string
    {
        $texto = mb_strtolower($nome . ' ' . $slug, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);

        foreach ([
            'metal' => ['metal', 'alumin', 'aco', 'cobre', 'ferro', 'lata'],
            'plastico' => ['plast'],
            'papel' => ['papel', 'cartao'],
            'vidro' => ['vidro'],
            'organico' => ['organic', 'compost', 'aliment'],
            'oleo' => ['oleo'],
            'eletronico' => ['eletron', 'pilha', 'bateria'],
        ] as $categoria => $termos) {
            foreach ($termos as $termo) {
                if (str_contains($texto, $termo)) {
                    return $categoria;
                }
            }
        }

        return 'outros';
    }
}
