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
$repo->prepararDemo();

$mensagem = null;
$tipoMensagem = 'sucesso';

if (isset($_GET['qr'])) {
    $resultado = $repo->associarSessao((string) $_GET['qr'], (int) $usuario['id']);
    if ($resultado['sucesso']) {
        header('Location: ' . Url::para('/station/?associado=1'));
        exit;
    }

    $mensagem = $resultado['mensagem'];
    $tipoMensagem = 'erro';
}

$saldo = $repo->saldoDoUsuario((int) $usuario['id']);
$estacoes = $repo->estacoesAtivas();
$descartes = $repo->descartesDoUsuario((int) $usuario['id']);
$ranking = $repo->rankingMensal();
$recompensas = $repo->recompensasAtivas();
$vouchers = $repo->vouchersDoUsuario((int) $usuario['id']);
$posicao = $repo->posicaoUsuarioNoMes((int) $usuario['id']);

$primeiroNome = explode(' ', $usuario['nome'])[0];
$iniciais = strtoupper(substr($primeiroNome, 0, 1));
$ultimoDescarte = $descartes[0] ?? null;
$paginaAtualApp = 'inicio';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio - EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/app.css') ?>">
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/../../components/app/sidebar.php'; ?>

        <main class="conteudo-app">
            <div class="app-topbar">
                <div>
                    <h1>Ola, <?= htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p>Um resumo rapido da sua conta EcoDrop.</p>
                </div>
                <div class="perfil-pill">
                    <span><?= htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>

            <?php if ($mensagem !== null): ?>
                <div class="alerta alerta-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>" role="status">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <section class="dashboard-hero">
                <article class="cartao-principal">
                    <span class="etiqueta">Fluxo principal</span>
                    <h2>Descarte reciclaveis, acumule pontos e troque por beneficios.</h2>
                    <p>Acesse a estacao demo para simular um descarte ou use os atalhos para ver seus dados completos.</p>
                    <div class="acoes-hero">
                        <a class="botao botao-primario" href="<?= Url::escapar('/station/') ?>">Iniciar descarte</a>
                        <a class="botao botao-secundario" href="<?= Url::escapar('/app/saques/') ?>">Ver saques</a>
                    </div>
                </article>

                <article class="cartao-resumo">
                    <span>Saldo atual</span>
                    <strong><?= number_format($saldo, 0, ',', '.') ?></strong>
                    <em>pontos disponiveis</em>
                </article>

                <article class="cartao-resumo">
                    <span>Ranking mensal</span>
                    <strong><?= $posicao === null ? '-' : $posicao . 'o' ?></strong>
                    <em><?= $posicao === null ? 'sem posicao' : 'posicao atual' ?></em>
                </article>
            </section>

            <section class="atalhos-dashboard" aria-label="Atalhos">
                <a class="atalho-card" href="<?= Url::escapar('/app/saques/') ?>">
                    <span>Saques</span>
                    <strong>Recompensas e vouchers</strong>
                    <em><?= count($recompensas) ?> recompensas / <?= count($vouchers) ?> vouchers</em>
                </a>
                <a class="atalho-card" href="<?= Url::escapar('/app/extrato/') ?>">
                    <span>Extrato</span>
                    <strong>Pontos e descartes</strong>
                    <em><?= count($descartes) ?> descartes recentes</em>
                </a>
                <a class="atalho-card" href="<?= Url::escapar('/station/') ?>">
                    <span>Estacao</span>
                    <strong>Simular descarte</strong>
                    <em><?= count($estacoes) ?> estacoes ativas</em>
                </a>
            </section>

            <section class="bloco-operacao">
                <div>
                    <span class="etiqueta">Ultima atividade</span>
                    <h2><?= $ultimoDescarte ? 'Descarte registrado' : 'Nenhum descarte ainda' ?></h2>
                    <p>
                        <?php if ($ultimoDescarte): ?>
                            <?= htmlspecialchars($ultimoDescarte['material'], ENT_QUOTES, 'UTF-8') ?> em <?= date('d/m H:i', strtotime((string) $ultimoDescarte['criado_em'])) ?>.
                        <?php else: ?>
                            Comece pela estacao demo para gerar sua primeira movimentacao.
                        <?php endif; ?>
                    </p>
                </div>
                <a class="botao botao-secundario" href="<?= Url::escapar('/app/extrato/') ?>">Abrir extrato</a>
            </section>

            <section class="painel-app">
                <div class="cabecalho-painel">
                    <h2>Top ranking mensal</h2>
                    <span>Resumo</span>
                </div>
                <ol class="ranking">
                    <?php foreach (array_slice($ranking, 0, 3) as $indice => $linha): ?>
                        <li>
                            <span><?= $indice + 1 ?></span>
                            <strong><?= htmlspecialchars(EcoDropRepository::nomeRanking($linha['nome']), ENT_QUOTES, 'UTF-8') ?></strong>
                            <em><?= number_format((int) $linha['pontos_mes'], 0, ',', '.') ?> pts</em>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php if (empty($ranking)): ?>
                    <p class="estado-vazio">O ranking aparece assim que houver pontos no mes.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
