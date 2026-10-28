<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/EcoDropRepository.php';

/**
 * Histórico e nova pesagem do usuário.
 *
 * A pesagem é feita a partir da lixeira escolhida no app. O repositório
 * reaproveita as estações cadastradas e delega ao EcoDropRepository a criação
 * e conclusão da sessão interna, mantendo o vínculo exigido pelos descartes.
 */
final class MeusDescartesRepository
{
    private const LIMITE_HISTORICO = 80;

    private EcoDropRepository $ecodrop;

    public function __construct()
    {
        $this->ecodrop = new EcoDropRepository();
    }

    public function resumo(int $usuarioId): array
    {
        $stmt = Database::conexao()->prepare(
            "SELECT COUNT(*) AS pesagens,
                    COUNT(DISTINCT d.material_id) AS tipos,
                    COALESCE(SUM(CASE WHEN m.unidade = 'kg' THEN d.quantidade ELSE 0 END), 0) AS kg,
                    COALESCE(SUM(CASE WHEN m.unidade = 'l' THEN d.quantidade ELSE 0 END), 0) AS litros,
                    COALESCE(SUM(d.pontos_gerados), 0) AS pontos,
                    MAX(d.criado_em) AS ultimo
             FROM descartes d
             INNER JOIN materiais m ON m.id = d.material_id
             WHERE d.usuario_id = :usuario_id"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        $linha = $stmt->fetch() ?: [];

        return [
            'pesagens' => (int) ($linha['pesagens'] ?? 0),
            'tipos' => (int) ($linha['tipos'] ?? 0),
            'kg' => (float) ($linha['kg'] ?? 0),
            'litros' => (float) ($linha['litros'] ?? 0),
            'pontos' => (int) ($linha['pontos'] ?? 0),
            'ultimo' => $linha['ultimo'] ?? null,
        ];
    }

    public function historico(int $usuarioId): array
    {
        $stmt = Database::conexao()->prepare(
            "SELECT d.id, d.quantidade, d.quantidade_valida, d.pontos_gerados,
                    d.status, d.criado_em, m.nome AS material, m.slug AS material_slug,
                    m.unidade, e.nome AS estacao, e.codigo AS lixeira_codigo
             FROM descartes d
             INNER JOIN materiais m ON m.id = d.material_id
             INNER JOIN estacoes e ON e.id = d.estacao_id
             WHERE d.usuario_id = :usuario_id
             ORDER BY d.criado_em DESC, d.id DESC
             LIMIT " . self::LIMITE_HISTORICO
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function tiposRecolhidos(int $usuarioId): array
    {
        $stmt = Database::conexao()->prepare(
            "SELECT m.id, m.nome, m.unidade,
                    COUNT(*) AS pesagens,
                    SUM(d.quantidade) AS quantidade,
                    SUM(d.pontos_gerados) AS pontos
             FROM descartes d
             INNER JOIN materiais m ON m.id = d.material_id
             WHERE d.usuario_id = :usuario_id
             GROUP BY m.id, m.nome, m.unidade
             ORDER BY quantidade DESC, pesagens DESC, m.nome"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function materiaisAtivos(): array
    {
        return $this->ecodrop->materiaisAtivos();
    }

    public function lixeirasAtivas(): array
    {
        return $this->ecodrop->estacoesAtivas();
    }

    public function registrarPesagem(int $usuarioId, int $estacaoId, int $materialId, float $quantidade): array
    {
        if ($estacaoId < 1 || $materialId < 1) {
            return ['sucesso' => false, 'mensagem' => 'Selecione a lixeira, o material e informe a quantidade.'];
        }

        return $this->ecodrop->registrarDescarteDireto($usuarioId, $estacaoId, $materialId, $quantidade);
    }
}
