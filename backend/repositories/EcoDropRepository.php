<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

final class EcoDropRepository
{
    public function prepararDemo(): void
    {
        $pdo = Database::conexao();

        $pdo->exec(
            "UPDATE materiais
             SET pontos_por_unidade = CASE slug
                WHEN 'plastico' THEN 150
                WHEN 'papel' THEN 90
                WHEN 'vidro' THEN 120
                WHEN 'oleo-cozinha-usado' THEN 220
                ELSE pontos_por_unidade
             END
             WHERE pontos_por_unidade IS NULL"
        );

        $stmt = $pdo->prepare(
            "INSERT INTO configuracoes (chave, valor, descricao)
             VALUES ('bonus_primeiro_descarte_pontos', '100', 'Valor ficticio usado no MVP local.')
             ON DUPLICATE KEY UPDATE valor = COALESCE(valor, VALUES(valor))"
        );
        $stmt->execute();

        $stmt = $pdo->prepare(
            "INSERT INTO configuracoes (chave, valor, descricao)
             VALUES ('sessao_estacao_uso_segundos', '900', 'Tempo maximo, em segundos, que uma sessao em uso aguarda o descarte no totem.')
             ON DUPLICATE KEY UPDATE valor = COALESCE(valor, VALUES(valor))"
        );
        $stmt->execute();

        $pdo->exec(
            "INSERT INTO estacoes (nome, endereco, latitude, longitude, status)
             SELECT 'EcoDrop Centro', 'Praca Central, 100', -23.5505200, -46.6333080, 'ativa'
             WHERE NOT EXISTS (SELECT 1 FROM estacoes WHERE nome = 'EcoDrop Centro')"
        );
        $pdo->exec(
            "INSERT INTO estacoes (nome, endereco, latitude, longitude, status)
             SELECT 'EcoDrop Parque Verde', 'Rua das Arvores, 42', -23.5558000, -46.6401000, 'ativa'
             WHERE NOT EXISTS (SELECT 1 FROM estacoes WHERE nome = 'EcoDrop Parque Verde')"
        );

        $pdo->exec(
            "INSERT INTO parceiros (nome, descricao, status)
             SELECT 'Mercado Verde', 'Parceiro ficticio para recompensas do MVP.', 'ativo'
             WHERE NOT EXISTS (SELECT 1 FROM parceiros WHERE nome = 'Mercado Verde')"
        );
        $pdo->exec(
            "INSERT INTO parceiros (nome, descricao, status)
             SELECT 'Cafe Circular', 'Parceiro ficticio para vouchers sustentaveis.', 'ativo'
             WHERE NOT EXISTS (SELECT 1 FROM parceiros WHERE nome = 'Cafe Circular')"
        );

        $pdo->exec(
            "INSERT INTO recompensas (parceiro_id, nome, descricao, custo_pontos, estoque, validade_dias, status)
             SELECT p.id, 'Cupom Mercado Verde', 'Desconto ficticio de R$ 10 em compras selecionadas.', 450, 25, 30, 'ativo'
             FROM parceiros p WHERE p.nome = 'Mercado Verde'
                AND NOT EXISTS (SELECT 1 FROM recompensas WHERE nome = 'Cupom Mercado Verde')"
        );

        $pdo->exec(
            "INSERT INTO recompensas (parceiro_id, nome, descricao, custo_pontos, estoque, validade_dias, status)
             SELECT p.id, 'Cafe sustentavel', 'Voucher ficticio para uma bebida pequena.', 300, 40, 15, 'ativo'
             FROM parceiros p
             WHERE p.nome = 'Cafe Circular'
                AND NOT EXISTS (SELECT 1 FROM recompensas WHERE nome = 'Cafe sustentavel')"
        );
    }

    public function estacoesAtivas(): array
    {
        return Database::conexao()
            ->query("SELECT * FROM estacoes WHERE status = 'ativa' ORDER BY nome")
            ->fetchAll();
    }

    public function materiaisAtivos(): array
    {
        return Database::conexao()
            ->query("SELECT * FROM materiais WHERE status = 'ativo' ORDER BY nome")
            ->fetchAll();
    }

    public function recompensasAtivas(): array
    {
        return Database::conexao()
            ->query(
                "SELECT r.*, p.nome AS parceiro
                 FROM recompensas r
                 LEFT JOIN parceiros p ON p.id = r.parceiro_id
                 WHERE r.status = 'ativo'
                 ORDER BY r.custo_pontos IS NULL, r.custo_pontos, r.nome"
            )
            ->fetchAll();
    }

    public function saldoDoUsuario(int $usuarioId): int
    {
        $stmt = Database::conexao()->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'credito' THEN quantidade ELSE -quantidade END), 0)
             FROM movimentacoes_pontos
             WHERE usuario_id = :usuario_id"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function descartesDoUsuario(int $usuarioId): array
    {
        $stmt = Database::conexao()->prepare(
            "SELECT d.*, m.nome AS material, m.slug AS material_slug, m.unidade, e.nome AS estacao
             FROM descartes d
             INNER JOIN materiais m ON m.id = d.material_id
             INNER JOIN estacoes e ON e.id = d.estacao_id
             WHERE d.usuario_id = :usuario_id
             ORDER BY d.criado_em DESC
             LIMIT 8"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function extratoDoUsuario(int $usuarioId): array
    {
        $stmt = Database::conexao()->prepare(
            "SELECT *
             FROM movimentacoes_pontos
             WHERE usuario_id = :usuario_id
             ORDER BY criado_em DESC
             LIMIT 10"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function vouchersDoUsuario(int $usuarioId): array
    {
        $stmt = Database::conexao()->prepare(
            "SELECT v.*, r.nome AS recompensa
             FROM vouchers v
             INNER JOIN resgates rg ON rg.id = v.resgate_id
             INNER JOIN recompensas r ON r.id = rg.recompensa_id
             WHERE v.usuario_id = :usuario_id
             ORDER BY v.criado_em DESC
             LIMIT 6"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function rankingMensal(bool $limitar = true): array
    {
        return Database::conexao()
            ->query(
                "SELECT u.id AS usuario_id, u.nome,
                    COALESCE(SUM(mp.quantidade), 0) AS pontos_mes
                 FROM usuarios u
                 INNER JOIN movimentacoes_pontos mp ON mp.usuario_id = u.id
                    AND mp.tipo = 'credito'
                    AND mp.origem IN ('descarte', 'bonus_primeiro_descarte')
                    AND YEAR(mp.criado_em) = YEAR(CURRENT_DATE())
                    AND MONTH(mp.criado_em) = MONTH(CURRENT_DATE())
                 WHERE u.papel = 'usuario'
                 GROUP BY u.id, u.nome
                 ORDER BY pontos_mes DESC, u.nome, u.id " . ($limitar ? 'LIMIT 10' : '')
            )
            ->fetchAll();
    }

    public function posicaoUsuarioNoMes(int $usuarioId): ?int
    {
        foreach ($this->rankingMensal(false) as $indice => $linha) {
            if ((int) $linha['usuario_id'] === $usuarioId) {
                return $indice + 1;
            }
        }

        return null;
    }

    public function criarSessaoEstacao(int $estacaoId): array
    {
        $this->expirarSessoesVencidas();

        if (!$this->estacaoAtivaExiste($estacaoId)) {
            return [];
        }

        $this->cancelarSessoesAguardandoDaEstacao($estacaoId);

        $token = bin2hex(random_bytes(32));
        $validadeSegundos = $this->validadeQrSegundos();
        $expiraEm = date('Y-m-d H:i:s', time() + $validadeSegundos);

        $stmt = Database::conexao()->prepare(
            "INSERT INTO sessoes_estacao (estacao_id, token_hash, expira_em)
             VALUES (:estacao_id, :token_hash, :expira_em)"
        );
        $stmt->bindValue('estacao_id', $estacaoId, PDO::PARAM_INT);
        $stmt->bindValue('token_hash', hash('sha256', $token));
        $stmt->bindValue('expira_em', $expiraEm);
        $stmt->execute();

        // O token puro é devolvido apenas aqui (para montar o QR/link), nunca persistido.
        $sessao = $this->sessaoPorToken($token);
        return $sessao === null ? [] : $sessao + ['token' => $token];
    }

    /**
     * Descreve a sessão a partir do token, sem alterar estado. Usado pela página de
     * confirmação do app antes de associar a conta ao descarte.
     *
     * @return array{status: string, expira_em: string, estacao: string}|null
     */
    public function descreverSessao(string $token): ?array
    {
        if (preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
            return null;
        }

        $this->expirarSessoesVencidas();

        $stmt = Database::conexao()->prepare(
            "SELECT s.status, s.expira_em, e.nome AS estacao
             FROM sessoes_estacao s
             INNER JOIN estacoes e ON e.id = s.estacao_id
             WHERE s.token_hash = :token_hash
             LIMIT 1"
        );
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $sessao = $stmt->fetch();
        return $sessao ?: null;
    }

    public function associarSessao(string $token, int $usuarioId): array
    {        return Database::transacao(function (PDO $pdo) use ($token, $usuarioId): array {
            $this->expirarSessoesVencidas();
            $sessao = $this->sessaoPorToken($token, true);

            if ($sessao === null) {
                return ['sucesso' => false, 'mensagem' => 'Sessao nao encontrada.'];
            }

            if (strtotime((string) $sessao['expira_em']) < time()) {
                $this->marcarSessao($token, 'expirada');
                return ['sucesso' => false, 'mensagem' => 'QR Code expirado. Gere uma nova sessao na estacao.'];
            }

            if ($sessao['status'] !== 'aguardando_usuario') {
                return ['sucesso' => false, 'mensagem' => 'Esta sessao nao esta aguardando identificacao.'];
            }

            $this->cancelarOutrasSessoesEmUso($pdo, (int) $sessao['id'], (int) $sessao['estacao_id'], $usuarioId);

            $stmt = $pdo->prepare(
                "UPDATE sessoes_estacao
                 SET usuario_id = :usuario_id, status = 'em_uso', associado_em = :associado_em
                 WHERE token_hash = :token_hash"
            );
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'associado_em' => date('Y-m-d H:i:s'),
                'token_hash' => hash('sha256', $token),
            ]);

            return ['sucesso' => true, 'mensagem' => 'Sessao associada. A estacao ja pode simular o descarte.'];
        });
    }

    public function sessoesEmUso(): array
    {
        $this->expirarSessoesVencidas();

        // Não expõe o token de associação: a estação identifica a sessão pelo id.
        return Database::conexao()
            ->query(
                "SELECT s.id, s.estacao_id, s.usuario_id, s.associado_em, e.nome AS estacao, u.nome AS usuario
                 FROM sessoes_estacao s
                 INNER JOIN estacoes e ON e.id = s.estacao_id
                 INNER JOIN usuarios u ON u.id = s.usuario_id
                 WHERE s.status = 'em_uso'
                 ORDER BY s.associado_em DESC
                 LIMIT 20"
            )
            ->fetchAll();
    }

    public function registrarDescarte(string $token, int $materialId, float $quantidade): array
    {
        return Database::transacao(function (PDO $pdo) use ($token, $materialId, $quantidade): array {
            return $this->efetivarDescarte($pdo, $this->sessaoPorToken($token, true), $materialId, $quantidade);
        });
    }

    /**
     * Usado pela interface da estação: identifica a sessão pelo id (sem expor o token
     * de associação no HTML) e delega para a mesma regra de registro.
     */
    public function registrarDescartePorSessao(int $sessaoId, int $materialId, float $quantidade): array
    {
        return Database::transacao(function (PDO $pdo) use ($sessaoId, $materialId, $quantidade): array {
            return $this->efetivarDescarte($pdo, $this->sessaoPorId($sessaoId, true), $materialId, $quantidade);
        });
    }

    /**
     * @param array<string,mixed>|null $sessao
     * @return array{sucesso: bool, mensagem: string, pontos?: int}
     */
    private function efetivarDescarte(PDO $pdo, ?array $sessao, int $materialId, float $quantidade): array
    {
        if ($sessao === null || $sessao['status'] !== 'em_uso' || empty($sessao['usuario_id'])) {
            return ['sucesso' => false, 'mensagem' => 'Sessao invalida ou sem usuario identificado.'];
        }

        // A sessão em uso também tem prazo: sem esse limite, uma sessão ficaria
        // "em_uso" indefinidamente caso o descarte nunca fosse concluído no totem.
        $associadoEm = strtotime((string) ($sessao['associado_em'] ?? ''));
        if ($associadoEm === false || time() - $associadoEm > $this->sessaoUsoSegundos()) {
            $this->marcarSessaoPorId($pdo, (int) $sessao['id'], 'expirada');
            return ['sucesso' => false, 'mensagem' => 'A sessao de descarte expirou. Gere um novo QR na estacao.'];
        }

        $material = $this->materialPorId($materialId);
        if ($material === null || $material['status'] !== 'ativo') {
            return ['sucesso' => false, 'mensagem' => 'Material invalido.'];
        }

        if (!is_finite($quantidade) || $quantidade <= 0 || $quantidade > 9999999.999) {
            return ['sucesso' => false, 'mensagem' => 'Informe uma quantidade válida maior que zero.'];
        }

        $quantidadeValida = $quantidade >= (float) $material['quantidade_minima'];
        if ($quantidadeValida && $material['pontos_por_unidade'] === null) {
            // Material ativo porém sem pontuação configurada (pendência de negócio).
            return ['sucesso' => false, 'mensagem' => 'Material sem pontuacao configurada. Procure o suporte.'];
        }
        $pontosPorUnidade = (float) ($material['pontos_por_unidade'] ?? 0);
        $pontosCalculados = $quantidadeValida ? round($quantidade * $pontosPorUnidade) : 0;
        if (!is_finite((float) $pontosCalculados) || $pontosCalculados < 0 || $pontosCalculados > 4294967295) {
            return ['sucesso' => false, 'mensagem' => 'A quantidade excede o limite de pontuação por descarte.'];
        }
        $pontos = (int) $pontosCalculados;
        $status = $quantidadeValida ? 'valido' : 'invalido';

        $stmt = $pdo->prepare(
            "INSERT INTO descartes
                (sessao_estacao_id, usuario_id, estacao_id, material_id, quantidade, quantidade_valida, pontos_gerados, status)
             VALUES
                (:sessao_id, :usuario_id, :estacao_id, :material_id, :quantidade, :quantidade_valida, :pontos, :status)"
        );
        $stmt->execute([
            'sessao_id' => $sessao['id'],
            'usuario_id' => $sessao['usuario_id'],
            'estacao_id' => $sessao['estacao_id'],
            'material_id' => $materialId,
            'quantidade' => $quantidade,
            'quantidade_valida' => $quantidadeValida ? 1 : 0,
            'pontos' => $pontos,
            'status' => $status,
        ]);
        $descarteId = (int) $pdo->lastInsertId();

        if ($pontos > 0) {
            $this->registrarMovimentacao($pdo, (int) $sessao['usuario_id'], 'credito', 'descarte', $descarteId, $pontos, 'Descarte de ' . $material['nome']);
            $this->aplicarBonusPrimeiroDescarte($pdo, (int) $sessao['usuario_id']);
        }

        $stmt = $pdo->prepare("UPDATE sessoes_estacao SET status = 'concluida' WHERE id = :id");
        $stmt->execute(['id' => $sessao['id']]);

        return [
            'sucesso' => true,
            'mensagem' => $quantidadeValida ? 'Descarte registrado e pontos creditados.' : 'Descarte registrado, mas abaixo do minimo para pontuar.',
            'pontos' => $pontos,
        ];
    }

    public function resgatarRecompensa(int $usuarioId, int $recompensaId): array
    {
        return Database::transacao(function (PDO $pdo) use ($usuarioId, $recompensaId): array {
            // Serializa resgates da mesma carteira, inclusive de recompensas diferentes.
            $bloquearUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id AND status = 'ativo' FOR UPDATE");
            $bloquearUsuario->execute(['id' => $usuarioId]);
            if ($bloquearUsuario->fetch() === false) {
                return ['sucesso' => false, 'mensagem' => 'Conta indisponivel para resgate.'];
            }
            $stmt = $pdo->prepare("SELECT * FROM recompensas WHERE id = :id AND status = 'ativo' FOR UPDATE");
            $stmt->execute(['id' => $recompensaId]);
            $recompensa = $stmt->fetch();

            if (!$recompensa) {
                return ['sucesso' => false, 'mensagem' => 'Recompensa indisponivel.'];
            }

            $custo = (int) ($recompensa['custo_pontos'] ?? 0);
            if ($custo <= 0) {
                return ['sucesso' => false, 'mensagem' => 'Custo da recompensa ainda nao foi configurado.'];
            }

            if ($recompensa['estoque'] !== null && (int) $recompensa['estoque'] <= 0) {
                return ['sucesso' => false, 'mensagem' => 'Recompensa sem estoque.'];
            }

            if ($this->saldoDoUsuario($usuarioId) < $custo) {
                return ['sucesso' => false, 'mensagem' => 'Saldo insuficiente para este resgate.'];
            }

            $stmt = $pdo->prepare(
                "INSERT INTO resgates (usuario_id, recompensa_id, pontos_debitados)
                 VALUES (:usuario_id, :recompensa_id, :pontos)"
            );
            $stmt->execute(['usuario_id' => $usuarioId, 'recompensa_id' => $recompensaId, 'pontos' => $custo]);
            $resgateId = (int) $pdo->lastInsertId();

            $codigo = 'ECO-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            $validadeSql = $recompensa['validade_dias'] === null
                ? 'NULL'
                : 'DATE_ADD(NOW(), INTERVAL ' . (int) $recompensa['validade_dias'] . ' DAY)';

            $stmt = $pdo->prepare(
                "INSERT INTO vouchers (resgate_id, usuario_id, codigo, qr_code_token, validade_em)
                 VALUES (:resgate_id, :usuario_id, :codigo, :qr_code_token, {$validadeSql})"
            );
            $stmt->execute([
                'resgate_id' => $resgateId,
                'usuario_id' => $usuarioId,
                'codigo' => $codigo,
                'qr_code_token' => bin2hex(random_bytes(32)),
            ]);

            $this->registrarMovimentacao($pdo, $usuarioId, 'debito', 'resgate', $resgateId, $custo, 'Resgate: ' . $recompensa['nome']);

            if ($recompensa['estoque'] !== null) {
                $stmt = $pdo->prepare('UPDATE recompensas SET estoque = estoque - 1 WHERE id = :id');
                $stmt->execute(['id' => $recompensaId]);
            }

            return ['sucesso' => true, 'mensagem' => 'Recompensa resgatada. Voucher emitido: ' . $codigo];
        });
    }

    private function validadeQrSegundos(): int
    {
        $stmt = Database::conexao()->prepare("SELECT valor FROM configuracoes WHERE chave = 'qr_code_validade_segundos'");
        $stmt->execute();
        $valor = $stmt->fetchColumn();
        return is_numeric($valor) ? max(30, (int) $valor) : 120;
    }

    private function sessaoPorToken(string $token, bool $forUpdate = false): ?array
    {
        $sql = "SELECT * FROM sessoes_estacao WHERE token_hash = :token_hash";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = Database::conexao()->prepare($sql);
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $sessao = $stmt->fetch();
        return $sessao ?: null;
    }

    private function sessaoPorId(int $id, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM sessoes_estacao WHERE id = :id';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = Database::conexao()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $sessao = $stmt->fetch();
        return $sessao ?: null;
    }

    private function materialPorId(int $materialId): ?array
    {
        $stmt = Database::conexao()->prepare('SELECT * FROM materiais WHERE id = :id');
        $stmt->execute(['id' => $materialId]);
        $material = $stmt->fetch();
        return $material ?: null;
    }

    private function estacaoAtivaExiste(int $estacaoId): bool
    {
        $stmt = Database::conexao()->prepare("SELECT COUNT(*) FROM estacoes WHERE id = :id AND status = 'ativa'");
        $stmt->execute(['id' => $estacaoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function expirarSessoesVencidas(): void
    {
        // Comparações feitas com o relógio do PHP (mesma origem usada para gravar
        // expira_em/associado_em), evitando divergência com o fuso do MySQL.
        $agora = date('Y-m-d H:i:s');
        $stmt = Database::conexao()->prepare(
            "UPDATE sessoes_estacao
             SET status = 'expirada'
             WHERE status = 'aguardando_usuario'
                AND expira_em < :agora"
        );
        $stmt->execute(['agora' => $agora]);

        $limiteUso = date('Y-m-d H:i:s', time() - $this->sessaoUsoSegundos());
        $stmt = Database::conexao()->prepare(
            "UPDATE sessoes_estacao
             SET status = 'expirada'
             WHERE status = 'em_uso'
                AND associado_em IS NOT NULL
                AND associado_em < :limite"
        );
        $stmt->execute(['limite' => $limiteUso]);
    }

    private function sessaoUsoSegundos(): int
    {
        $stmt = Database::conexao()->prepare("SELECT valor FROM configuracoes WHERE chave = 'sessao_estacao_uso_segundos'");
        $stmt->execute();
        $valor = $stmt->fetchColumn();
        return is_numeric($valor) ? max(60, (int) $valor) : 900;
    }

    private function marcarSessao(string $token, string $status): void
    {
        $stmt = Database::conexao()->prepare('UPDATE sessoes_estacao SET status = :status WHERE token_hash = :token_hash');
        $stmt->execute(['status' => $status, 'token_hash' => hash('sha256', $token)]);
    }

    private function marcarSessaoPorId(PDO $pdo, int $id, string $status): void
    {
        $stmt = $pdo->prepare('UPDATE sessoes_estacao SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    private function cancelarSessoesAguardandoDaEstacao(int $estacaoId): void
    {
        $stmt = Database::conexao()->prepare(
            "UPDATE sessoes_estacao
             SET status = 'cancelada'
             WHERE estacao_id = :estacao_id
                AND status = 'aguardando_usuario'"
        );
        $stmt->execute(['estacao_id' => $estacaoId]);
    }

    private function cancelarOutrasSessoesEmUso(PDO $pdo, int $sessaoAtualId, int $estacaoId, int $usuarioId): void
    {
        $stmt = $pdo->prepare(
            "UPDATE sessoes_estacao
             SET status = 'cancelada'
             WHERE id <> :sessao_atual_id
                AND status = 'em_uso'
                AND (estacao_id = :estacao_id OR usuario_id = :usuario_id)"
        );
        $stmt->execute([
            'sessao_atual_id' => $sessaoAtualId,
            'estacao_id' => $estacaoId,
            'usuario_id' => $usuarioId,
        ]);
    }

    private function registrarMovimentacao(PDO $pdo, int $usuarioId, string $tipo, string $origem, int $origemId, int $quantidade, string $descricao): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO movimentacoes_pontos (usuario_id, tipo, origem, origem_tipo, origem_id, quantidade, descricao)
             VALUES (:usuario_id, :tipo, :origem, :origem_tipo, :origem_id, :quantidade, :descricao)"
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'origem' => $origem,
            'origem_tipo' => $origem,
            'origem_id' => $origemId,
            'quantidade' => $quantidade,
            'descricao' => $descricao,
        ]);
    }

    private function aplicarBonusPrimeiroDescarte(PDO $pdo, int $usuarioId): void
    {
        $stmt = $pdo->prepare('SELECT bonus_primeiro_descarte_aplicado FROM usuarios WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $usuarioId]);

        if ((int) $stmt->fetchColumn() === 1) {
            return;
        }

        $stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'bonus_primeiro_descarte_pontos'");
        $stmt->execute();
        $bonus = (int) $stmt->fetchColumn();

        if ($bonus > 0) {
            $this->registrarMovimentacao($pdo, $usuarioId, 'credito', 'bonus_primeiro_descarte', $usuarioId, $bonus, 'Bonus de primeiro descarte');
        }

        $stmt = $pdo->prepare('UPDATE usuarios SET bonus_primeiro_descarte_aplicado = 1 WHERE id = :id');
        $stmt->execute(['id' => $usuarioId]);
    }

    public static function nomeRanking(string $nomeCompleto): string
    {
        $partes = preg_split('/\s+/', trim($nomeCompleto)) ?: [];
        $primeiro = $partes[0] ?? 'Usuario';
        $ultimo = count($partes) > 1 ? end($partes) : '';

        return $ultimo !== '' ? $primeiro . ' ' . substr($ultimo, 0, 1) . '.' : $primeiro;
    }
}
