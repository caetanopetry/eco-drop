<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

final class AdminUsuariosRepository
{
    public function visaoGeral(): array
    {
        $pdo = Database::conexao();
        $agora = new DateTimeImmutable($pdo->query('SELECT NOW()')->fetchColumn());
        $linhas = $pdo->query(
            "SELECT u.id, u.nome, u.email, u.papel, u.status, u.criado_em,
                    (u.google_sub IS NOT NULL) AS google_vinculado,
                    a.ultimo_acesso, COALESCE(a.online, 0) AS online,
                    COALESCE(p.saldo, 0) AS saldo, COALESCE(d.total, 0) AS descartes
             FROM usuarios u
             LEFT JOIN (
                SELECT usuario_id, MAX(iniciado_em) AS ultimo_acesso,
                       MAX(encerrado_em IS NULL AND ultima_atividade_em >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS online
                FROM sessoes_acesso GROUP BY usuario_id
             ) a ON a.usuario_id = u.id
             LEFT JOIN (
                SELECT usuario_id, SUM(CASE WHEN tipo = 'credito' THEN quantidade ELSE -CAST(quantidade AS SIGNED) END) AS saldo
                FROM movimentacoes_pontos GROUP BY usuario_id
             ) p ON p.usuario_id = u.id
             LEFT JOIN (SELECT usuario_id, COUNT(*) AS total FROM descartes GROUP BY usuario_id) d ON d.usuario_id = u.id
             ORDER BY u.criado_em DESC, u.id DESC"
        )->fetchAll();
        $grafico = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = $agora->modify("-$i days");
            $grafico[$dia->format('Y-m-d')] = ['dia' => $dia->format('d/m'), 'total' => 0];
        }
        $contas = [];
        $indicadores = ['total' => count($linhas), 'online' => 0, 'novas' => 0, 'ativas' => 0, 'bloqueadas' => 0, 'inativas' => 0];
        foreach ($linhas as $u) {
            $online = (bool) $u['online'];
            $data = new DateTimeImmutable($u['criado_em']);
            $chave = $data->format('Y-m-d');
            if (isset($grafico[$chave])) {
                $grafico[$chave]['total']++;
                $indicadores['novas']++;
            }
            $indicadores['online'] += (int) $online;
            $indicadores['ativas'] += (int) ($u['status'] === 'ativo');
            $indicadores['bloqueadas'] += (int) ($u['status'] === 'bloqueado');
            $indicadores['inativas'] += (int) ($u['status'] === 'inativo');
            $contas[] = [
                (int) $u['id'], $u['nome'], $u['email'],
                $u['papel'] === 'admin' ? 'Administrador' : 'Usuário',
                ucfirst($u['status']), $online ? 'Online' : ($u['ultimo_acesso'] ? 'Offline' : 'Sem registro'),
                $data->format('d/m/Y'),
                $u['ultimo_acesso'] ? (new DateTimeImmutable($u['ultimo_acesso']))->format('d/m/Y H:i') : 'Sem registro',
                $u['google_vinculado'] ? 'Vinculada' : 'Não vinculada', (int) $u['saldo'], (int) $u['descartes']
            ];
        }
        $atividade = $pdo->query(
            "SELECT nome, tipo, ocorrido_em FROM (
                SELECT u.nome, 'cadastro' AS tipo, u.criado_em AS ocorrido_em FROM usuarios u
                UNION ALL
                SELECT u.nome, 'login', s.iniciado_em FROM sessoes_acesso s JOIN usuarios u ON u.id = s.usuario_id
                UNION ALL
                SELECT u.nome, 'logout', s.encerrado_em FROM sessoes_acesso s JOIN usuarios u ON u.id = s.usuario_id WHERE s.encerrado_em IS NOT NULL
             ) eventos ORDER BY ocorrido_em DESC LIMIT 3"
        )->fetchAll();
        return ['contas' => $contas, 'indicadores' => $indicadores, 'grafico' => array_values($grafico), 'atividade' => $atividade, 'atualizado' => $agora->format('d/m/Y H:i:s')];
    }
}
