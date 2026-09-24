<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Consultas agregadas para o dashboard do Admin.
 * Mostra apenas métricas reais que o banco já suporta (seção 18 do briefing:
 * "sem inventar métricas que o sistema não possui").
 */
final class AdminDashboardRepository
{
    public function indicadores(): array
    {
        $pdo = Database::conexao();

        return [
            'usuarios_cadastrados' => (int) $pdo->query(
                "SELECT COUNT(*) FROM usuarios WHERE papel = 'usuario'"
            )->fetchColumn(),
            'estacoes_ativas' => (int) $pdo->query(
                "SELECT COUNT(*) FROM estacoes WHERE status = 'ativa'"
            )->fetchColumn(),
            'materiais_cadastrados' => (int) $pdo->query(
                'SELECT COUNT(*) FROM materiais'
            )->fetchColumn(),
            'descartes_registrados' => (int) $pdo->query(
                'SELECT COUNT(*) FROM descartes'
            )->fetchColumn(),
            'recompensas_disponiveis' => (int) $pdo->query(
                "SELECT COUNT(*) FROM recompensas WHERE status = 'ativo'"
            )->fetchColumn(),
        ];
    }
}
