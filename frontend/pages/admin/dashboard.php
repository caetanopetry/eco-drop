<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminDashboardRepository.php';
require_once __DIR__ . '/../../../backend/repositories/EcoDropRepository.php';

Auth::iniciarSessao();
$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
header('Cache-Control: no-store');

$repo = new EcoDropRepository();

$indicadores = (new AdminDashboardRepository())->indicadores();
$materiais = $repo->materiaisAtivos();
$recompensas = $repo->recompensasAtivas();
$ranking = $repo->rankingMensal();
$paginaAtual = 'dashboard';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - EcoDrop Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/admin.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body>
    <div class="layout-admin">
        <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>

        <main class="admin-conteudo">
            <div class="admin-cabecalho">
                <div>
                    <h1>Dashboard</h1>
                    <p class="texto-suave" style="margin:0;">Visão geral operacional do MVP fictício em funcionamento.</p>
                </div>
                <div class="flex itens-centro gap-3"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?>
                    <span class="texto-suave"><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                    <a class="botao botao-secundario" href="<?= Url::escapar('/station/') ?>">Estação demo</a>
                    <?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?>
                </div>
            </div>

            <section class="regua-indicadores">
                <div class="item">
                    <p class="valor"><?= number_format($indicadores['usuarios_cadastrados'], 0, ',', '.') ?></p>
                    <p class="rotulo">Usuários cadastrados</p>
                </div>
                <div class="item">
                    <p class="valor"><?= number_format($indicadores['estacoes_ativas'], 0, ',', '.') ?></p>
                    <p class="rotulo">Estações ativas</p>
                </div>
                <div class="item">
                    <p class="valor"><?= number_format($indicadores['materiais_cadastrados'], 0, ',', '.') ?></p>
                    <p class="rotulo">Materiais cadastrados</p>
                </div>
                <div class="item">
                    <p class="valor"><?= number_format($indicadores['descartes_registrados'], 0, ',', '.') ?></p>
                    <p class="rotulo">Descartes registrados</p>
                </div>
                <div class="item">
                    <p class="valor"><?= number_format($indicadores['recompensas_disponiveis'], 0, ',', '.') ?></p>
                    <p class="rotulo">Recompensas disponíveis</p>
                </div>
            </section>

            <section class="admin-grade">
                <div class="admin-painel">
                    <h2>Materiais e pontuação</h2>
                    <p class="tabela-dica">Deslize a tabela para ver todas as colunas.</p>
                    <div class="tabela-wrapper" role="region" aria-label="Materiais e pontuação, tabela com rolagem horizontal" tabindex="0">
                        <table class="tabela-ecodrop">
                            <thead><tr><th>Material</th><th>Minimo</th><th>Pontos/unidade</th></tr></thead>
                            <tbody>
                                <?php foreach ($materiais as $material): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($material['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((float) $material['quantidade_minima'], 3, ',', '.') ?> <?= htmlspecialchars($material['unidade'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((float) $material['pontos_por_unidade'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-painel">
                    <h2>Ranking mensal</h2>
                    <p class="tabela-dica">Deslize a tabela para ver todas as colunas.</p>
                    <div class="tabela-wrapper" role="region" aria-label="Ranking mensal, tabela com rolagem horizontal" tabindex="0">
                        <table class="tabela-ecodrop">
                            <thead><tr><th>#</th><th>Usuario</th><th>Pontos</th></tr></thead>
                            <tbody>
                                <?php foreach ($ranking as $indice => $linha): ?>
                                    <tr>
                                        <td><?= $indice + 1 ?></td>
                                        <td><?= htmlspecialchars(EcoDropRepository::nomeRanking($linha['nome']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) $linha['pontos_mes'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($ranking)): ?>
                                    <tr><td colspan="3">Sem pontuação no mês.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-painel admin-painel-largo">
                    <h2>Recompensas ficticias</h2>
                    <p class="tabela-dica">Deslize a tabela para ver todas as colunas.</p>
                    <div class="tabela-wrapper" role="region" aria-label="Recompensas, tabela com rolagem horizontal" tabindex="0">
                        <table class="tabela-ecodrop">
                            <thead><tr><th>Recompensa</th><th>Parceiro</th><th>Custo</th><th>Estoque</th></tr></thead>
                            <tbody>
                                <?php foreach ($recompensas as $recompensa): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($recompensa['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($recompensa['parceiro'] ?? 'EcoDrop', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) $recompensa['custo_pontos'], 0, ',', '.') ?> pts</td>
                                        <td><?= $recompensa['estoque'] === null ? 'Ilimitado' : number_format((int) $recompensa['estoque'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
