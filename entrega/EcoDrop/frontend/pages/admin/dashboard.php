<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminDashboardRepository.php';

Auth::iniciarSessao();
$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));

$indicadores = (new AdminDashboardRepository())->indicadores();
$paginaAtual = 'dashboard';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — EcoDrop Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/admin.css') ?>">
</head>
<body>
    <div class="layout-admin">
        <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>

        <main class="admin-conteudo">
            <div class="admin-cabecalho">
                <div>
                    <h1>Dashboard</h1>
                    <p class="texto-suave" style="margin:0;">Visão geral da plataforma.</p>
                </div>
                <div class="flex itens-centro gap-3">
                    <span class="texto-suave"><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                    <a class="botao botao-secundario" href="<?= Url::escapar('/app/logout/') ?>">Sair</a>
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

            <p class="texto-suave" style="max-width:60ch;">A gestão de usuários, estações, materiais, recompensas, parceiros, vouchers, ranking e premiações é liberada nas próximas fases de implementação.</p>
        </main>
    </div>
</body>
</html>
