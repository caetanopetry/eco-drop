<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/EcoDropRepository.php';

Auth::iniciarSessao();
$usuario = Auth::exigirAutenticacaoPagina(Url::para('/app/login/'));

if ($usuario['papel'] === 'admin') {
    header('Location: ' . Url::para('/admin/dashboard/'));
    exit;
}

$repo = new EcoDropRepository();

$saldo = $repo->saldoDoUsuario((int) $usuario['id']);
$descartes = $repo->descartesDoUsuario((int) $usuario['id']);
$extrato = $repo->extratoDoUsuario((int) $usuario['id']);
$paginaAtualApp = 'extrato';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Extrato - EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/app.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/app.css') ?>">
    <script src="<?= Url::escapar('/frontend/assets/js/conta.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/conta.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/../../components/app/sidebar.php'; ?>

        <main class="conteudo-app">
            <div class="app-topbar">
                <div>
                    <h1>Extrato</h1>
                    <p>Movimentacoes de pontos e historico de descartes.</p>
                </div>
                <?php include __DIR__ . '/../../components/app/perfil.php'; ?>
            </div>

            <section class="pagina-cabecalho">
                <article class="cartao-resumo">
                    <span>Saldo atual</span>
                    <strong><?= number_format($saldo, 0, ',', '.') ?></strong>
                    <em>pontos</em>
                </article>
                <article class="cartao-resumo">
                    <span>Descartes recentes</span>
                    <strong><?= number_format(count($descartes), 0, ',', '.') ?></strong>
                    <em>registros encontrados</em>
                </article>
            </section>

            <section class="grade-dashboard">
                <article class="painel-app painel-tabela">
                    <div class="cabecalho-painel">
                        <h2>Historico de descartes</h2>
                        <span><?= number_format(count($descartes), 0, ',', '.') ?> registros</span>
                    </div>
                    <?php if (empty($descartes)): ?>
                        <p class="estado-vazio">Nenhum descarte registrado.</p>
                    <?php else: ?>
                        <p class="tabela-dica">Deslize a tabela para ver todas as colunas.</p>
                        <div class="tabela-wrapper" role="region" aria-label="Histórico de descartes, tabela com rolagem horizontal" tabindex="0">
                            <table class="tabela-ecodrop">
                                <thead><tr><th>Data</th><th>Material</th><th>Quantidade</th><th>Estacao</th><th>Pontos</th></tr></thead>
                                <tbody>
                                    <?php foreach ($descartes as $descarte): ?>
                                        <tr>
                                            <td><?= date('d/m H:i', strtotime((string) $descarte['criado_em'])) ?></td>
                                            <td><?= htmlspecialchars($descarte['material'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= number_format((float) $descarte['quantidade'], 3, ',', '.') ?> <?= htmlspecialchars($descarte['unidade'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($descarte['estacao'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= number_format((int) $descarte['pontos_gerados'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="painel-app">
                    <h2>Movimentacoes</h2>
                    <ul class="lista-compacta extrato">
                        <?php foreach ($extrato as $movimento): ?>
                            <li>
                                <span><?= htmlspecialchars($movimento['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                                <strong class="<?= $movimento['tipo'] === 'credito' ? 'positivo' : 'negativo' ?>">
                                    <?= $movimento['tipo'] === 'credito' ? '+' : '-' ?><?= number_format((int) $movimento['quantidade'], 0, ',', '.') ?>
                                </strong>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($extrato)): ?>
                            <li><span>Nenhuma movimentacao.</span></li>
                        <?php endif; ?>
                    </ul>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
