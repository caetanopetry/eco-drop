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
$qrToken = null;
$qrSessao = null;

if (($_GET['associado'] ?? '') === '1') {
    $mensagem = 'Sessão da estação conectada. O operador pode registrar seu descarte na estação.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'associar_qr') {
    $tokenCsrf = $_POST['csrf_token'] ?? null;
    if (!is_string($tokenCsrf) || !Csrf::validar($tokenCsrf)) {
        $mensagem = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $tipoMensagem = 'erro';
    } else {
        $resultado = $repo->associarSessao((string) ($_POST['qr'] ?? ''), (int) $usuario['id']);
        if ($resultado['sucesso']) {
            header('Location: ' . Url::para('/app/inicio/?associado=1'));
            exit;
        }

        $mensagem = $resultado['mensagem'];
        $tipoMensagem = 'erro';
    }
} elseif (isset($_GET['qr'])) {
    // A leitura do QR (via link/câmera) apenas apresenta a confirmação; a associação
    // de fato ocorre em um POST com token CSRF, evitando conexão forçada por link.
    $qrToken = (string) $_GET['qr'];
    $qrSessao = $repo->descreverSessao($qrToken);
    if ($qrSessao === null || $qrSessao['status'] !== 'aguardando_usuario') {
        $qrToken = null;
        $qrSessao = null;
        $mensagem = 'Sessão da estação inválida, já utilizada ou expirada. Gere uma nova na estação.';
        $tipoMensagem = 'erro';
    }
}

$saldo = $repo->saldoDoUsuario((int) $usuario['id']);
$descartes = $repo->descartesDoUsuario((int) $usuario['id']);
$ranking = $repo->rankingMensal();
$recompensas = $repo->recompensasAtivas();
$vouchers = $repo->vouchersDoUsuario((int) $usuario['id']);
$posicao = $repo->posicaoUsuarioNoMes((int) $usuario['id']);

$primeiroNome = explode(' ', $usuario['nome'])[0];
$ultimoDescarte = $descartes[0] ?? null;
$paginaAtualApp = 'inicio';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio - EcoDrop</title>
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
                    <h1>Ola, <?= htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p>Um resumo rapido da sua conta EcoDrop.</p>
                </div>
                <?php include __DIR__ . '/../../components/app/perfil.php'; ?>
            </div>

            <?php if ($mensagem !== null): ?>
                <div class="alerta alerta-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>" role="<?= $tipoMensagem === 'erro' ? 'alert' : 'status' ?>">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($qrSessao !== null): ?>
                <section class="painel-app" aria-label="Confirmar conexão com a estação">
                    <div class="cabecalho-painel">
                        <h2>Conectar à estação</h2>
                        <span>Confirmação</span>
                    </div>
                    <p>Você está associando sua conta à sessão da estação <strong><?= htmlspecialchars($qrSessao['estacao'], ENT_QUOTES, 'UTF-8') ?></strong>. Confirme para permitir o registro do seu descarte.</p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="acao" value="associar_qr">
                        <input type="hidden" name="qr" value="<?= htmlspecialchars((string) $qrToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="botao botao-primario" type="submit">Conectar minha conta</button>
                    </form>
                </section>
            <?php endif; ?>

            <section class="dashboard-hero">
                <article class="cartao-principal">
                    <span class="etiqueta">Fluxo principal</span>
                    <h2>Descarte reciclaveis, acumule pontos e troque por beneficios.</h2>
                    <p>Em uma estação EcoDrop, conecte sua conta pelo link temporário exibido na estação para registrar o descarte.</p>
                    <div class="acoes-hero">
                        <a class="botao botao-primario" href="<?= Url::escapar('/app/extrato/') ?>">Ver extrato</a>
                        <a class="botao botao-secundario" href="<?= Url::escapar('/app/saques/') ?>">Ver resgates</a>
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
                    <span>Resgates</span>
                    <strong>Recompensas e vouchers</strong>
                    <em><?= count($recompensas) ?> recompensas / <?= count($vouchers) ?> vouchers</em>
                </a>
                <a class="atalho-card" href="<?= Url::escapar('/app/extrato/') ?>">
                    <span>Extrato</span>
                    <strong>Pontos e descartes</strong>
                    <em><?= count($descartes) ?> descartes recentes</em>
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
                            Conecte sua conta pelo link temporário em uma estação EcoDrop para registrar seu primeiro descarte.
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
