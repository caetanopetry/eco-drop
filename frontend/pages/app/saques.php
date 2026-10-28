<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/EcoDropRepository.php';

Auth::iniciarSessao();
$usuario = Auth::exigirAutenticacaoPagina(Url::para('/app/login/'));

if ($usuario['papel'] === 'admin') {
    header('Location: ' . Url::para('/admin/dashboard/'));
    exit;
}

$repo = new EcoDropRepository();

$mensagem = null;
$tipoMensagem = 'sucesso';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenCsrf = $_POST['csrf_token'] ?? null;
    if (!is_string($tokenCsrf) || !Csrf::validar($tokenCsrf)) {
        http_response_code(403);
        $mensagem = 'Token de seguranca invalido. Recarregue a pagina.';
        $tipoMensagem = 'erro';
    } elseif (($_POST['acao'] ?? '') === 'resgatar') {
        $resultado = $repo->resgatarRecompensa((int) $usuario['id'], (int) ($_POST['recompensa_id'] ?? 0));
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

$saldo = $repo->saldoDoUsuario((int) $usuario['id']);
$recompensas = $repo->recompensasAtivas();
$vouchers = $repo->vouchersDoUsuario((int) $usuario['id']);
$paginaAtualApp = 'saques';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resgates - EcoDrop</title>
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
                    <h1>Resgates</h1>
                    <p>Explore as trocas de pontos por recompensas e acompanhe seus vouchers.</p>
                </div>
                <?php include __DIR__ . '/../../components/app/perfil.php'; ?>
            </div>

            <p class="texto-suave" role="note">Ambiente de demonstração: pontos, recompensas e vouchers não têm valor monetário nem podem ser usados em estabelecimentos.</p>

            <?php if ($mensagem !== null): ?>
                <div class="alerta alerta-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>" role="<?= $tipoMensagem === 'erro' ? 'alert' : 'status' ?>">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <section class="pagina-cabecalho">
                <article class="cartao-resumo">
                    <span>Saldo disponivel</span>
                    <strong><?= number_format($saldo, 0, ',', '.') ?></strong>
                    <em>pontos para resgate</em>
                </article>
                <article class="cartao-resumo">
                    <span>Vouchers</span>
                    <strong><?= number_format(count($vouchers), 0, ',', '.') ?></strong>
                    <em>emitidos na conta</em>
                </article>
            </section>

            <section class="grade-dashboard">
                <article class="painel-app painel-recompensas" id="recompensas">
                    <div class="cabecalho-painel">
                        <h2>Recompensas disponiveis</h2>
                        <span><?= number_format(count($recompensas), 0, ',', '.') ?> opcoes</span>
                    </div>
                    <div class="grade-recompensas">
                        <?php foreach ($recompensas as $recompensa): ?>
                            <?php
                            $estoque = $recompensa['estoque'];
                            $esgotada = $estoque !== null && (int) $estoque <= 0;
                            $validadeDias = $recompensa['validade_dias'];
                            $faltamPontos = max(0, (int) $recompensa['custo_pontos'] - $saldo);
                            ?>
                            <article class="<?= $esgotada ? 'recompensa-esgotada' : '' ?>">
                                <span class="badge badge-neutro"><?= htmlspecialchars($recompensa['parceiro'] ?? 'EcoDrop', ENT_QUOTES, 'UTF-8') ?></span>
                                <h3><?= htmlspecialchars($recompensa['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($recompensa['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                <ul class="recompensa-detalhes">
                                    <li>
                                        <strong><?= $estoque === null ? 'Ilimitado' : number_format((int) $estoque, 0, ',', '.') ?></strong>
                                        <span><?= $estoque === null ? 'estoque disponível' : ((int) $estoque === 1 ? 'unidade em estoque' : 'unidades em estoque') ?></span>
                                    </li>
                                    <li>
                                        <strong><?= $validadeDias === null ? 'Sem validade' : number_format((int) $validadeDias, 0, ',', '.') . ' dias' ?></strong>
                                        <span><?= $validadeDias === null ? 'para usar o voucher' : 'de validade do voucher' ?></span>
                                    </li>
                                </ul>
                                <strong><?= number_format((int) $recompensa['custo_pontos'], 0, ',', '.') ?> pontos</strong>
                                <?php if ($esgotada): ?>
                                    <p class="recompensa-aviso" role="status">Recompensa esgotada. Aguarde a reposição do estoque.</p>
                                <?php elseif ($faltamPontos > 0): ?>
                                    <p class="recompensa-aviso">Faltam <?= number_format($faltamPontos, 0, ',', '.') ?> pontos para resgatar.</p>
                                <?php endif; ?>
                                <form method="post">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="acao" value="resgatar">
                                    <input type="hidden" name="recompensa_id" value="<?= (int) $recompensa['id'] ?>">
                                    <button class="botao botao-primario" type="submit" <?= ($esgotada || $faltamPontos > 0) ? 'disabled' : '' ?>><?= $esgotada ? 'Esgotada' : 'Resgatar' ?></button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                        <?php if (empty($recompensas)): ?>
                            <p class="estado-vazio">Nenhuma recompensa ativa no momento. Continue coletando pontos e volte em breve.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="painel-app" id="vouchers">
                    <h2>Meus vouchers</h2>
                    <ul class="lista-compacta">
                        <?php foreach ($vouchers as $voucher): ?>
                            <li>
                                <span>
                                    <strong><?= htmlspecialchars($voucher['codigo'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <?= htmlspecialchars($voucher['recompensa'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="badge badge-sucesso"><?= htmlspecialchars($voucher['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($vouchers)): ?>
                            <li><span>Nenhum voucher emitido.</span></li>
                        <?php endif; ?>
                    </ul>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
