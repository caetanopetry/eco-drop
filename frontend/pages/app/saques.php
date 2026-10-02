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
$repo->prepararDemo();

$mensagem = null;
$tipoMensagem = 'sucesso';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saques - EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/app.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/app.css') ?>">
    <script src="<?= Url::escapar('/frontend/assets/js/conta.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/conta.js') ?>" defer></script>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/../../components/app/sidebar.php'; ?>

        <main class="conteudo-app">
            <div class="app-topbar">
                <div>
                    <h1>Saques</h1>
                    <p>Troque pontos por recompensas e acompanhe seus vouchers.</p>
                </div>
                <?php include __DIR__ . '/../../components/app/perfil.php'; ?>
            </div>

            <?php if ($mensagem !== null): ?>
                <div class="alerta alerta-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>" role="status">
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
                            <article>
                                <span class="badge badge-neutro"><?= htmlspecialchars($recompensa['parceiro'] ?? 'EcoDrop', ENT_QUOTES, 'UTF-8') ?></span>
                                <h3><?= htmlspecialchars($recompensa['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($recompensa['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                <strong><?= number_format((int) $recompensa['custo_pontos'], 0, ',', '.') ?> pontos</strong>
                                <form method="post">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="acao" value="resgatar">
                                    <input type="hidden" name="recompensa_id" value="<?= (int) $recompensa['id'] ?>">
                                    <button class="botao botao-primario" type="submit" <?= $saldo < (int) $recompensa['custo_pontos'] ? 'disabled' : '' ?>>Resgatar</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
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
