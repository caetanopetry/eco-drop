<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/EcoDropRepository.php';

Auth::iniciarSessao();
// A estação representa o totem físico (AT03): somente administradores/operadores
// podem gerar sessões e registrar descartes que creditam pontos (RN021).
Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));

$repo = new EcoDropRepository();

$mensagem = null;
$tipoMensagem = 'sucesso';
$sessaoCriada = null;

if (($_GET['associado'] ?? '') === '1') {
    $mensagem = 'Sessao conectada. Agora selecione a sessao em uso, informe material e quantidade para registrar o descarte.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenCsrf = $_POST['csrf_token'] ?? null;
    if (!is_string($tokenCsrf) || !Csrf::validar($tokenCsrf)) {
        http_response_code(403);
        $mensagem = 'Token de segurança inválido. Recarregue a página.';
        $tipoMensagem = 'erro';
    } elseif (($_POST['acao'] ?? '') === 'criar_sessao') {
        $sessaoCriada = $repo->criarSessaoEstacao((int) ($_POST['estacao_id'] ?? 0));
        if ($sessaoCriada) {
            $mensagem = 'Sessão temporária criada. Abra a associação no app do usuário para continuar.';
        } else {
            $mensagem = 'Não foi possível criar a sessão: estação inválida ou inativa.';
            $tipoMensagem = 'erro';
        }
    } elseif (($_POST['acao'] ?? '') === 'registrar_descarte') {
        $resultado = $repo->registrarDescartePorSessao(
            (int) ($_POST['sessao_id'] ?? 0),
            (int) ($_POST['material_id'] ?? 0),
            (float) str_replace(',', '.', (string) ($_POST['quantidade'] ?? '0'))
        );
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

$estacoes = $repo->estacoesAtivas();
$materiais = $repo->materiaisAtivos();
$sessoes = $repo->sessoesEmUso();
$validadeMinutos = max(1, (int) ceil($repo->validadeSessaoSegundos() / 60));
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulador de estação - EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/station.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/station.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body class="pagina-station-demo">
    <main class="station-shell">
        <header class="station-topo">
            <a class="station-marca eco-marca eco-marca--clara" href="<?= Url::escapar('/app/inicio/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
        <?php require __DIR__ . '/../../components/theme/toggle.php'; ?>
        </header>

        <section class="station-hero">
            <div>
                <p class="station-etiqueta">Simulador de estação</p>
                <h1>Registre um descarte simulado</h1>
                <p>Crie uma sessão temporária, abra o link em outra janela com a conta do usuário e depois registre o material e a quantidade do descarte.</p>
            </div>

            <form class="station-form" method="post">
                <?= Csrf::campoHtml() ?>
                <input type="hidden" name="acao" value="criar_sessao">
                <label for="estacao_id">Estação</label>
                <select id="estacao_id" name="estacao_id" required>
                    <?php foreach ($estacoes as $estacao): ?>
                        <option value="<?= (int) $estacao['id'] ?>"><?= htmlspecialchars($estacao['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="botao station-botao-principal" type="submit">Criar sessão temporária</button>
            </form>
        </section>

        <?php if ($mensagem !== null): ?>
            <div class="alerta alerta-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>" role="<?= $tipoMensagem === 'erro' ? 'alert' : 'status' ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($sessaoCriada): ?>
            <?php $linkQr = Url::para('/app/inicio/?qr=' . urlencode((string) $sessaoCriada['token'])); ?>
            <section class="station-qr">
                <div class="sessao-selo" aria-hidden="true">
                    <span>Sessão</span>
                    <strong><?= htmlspecialchars(substr((string) $sessaoCriada['token'], 0, 6), ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= (int) $validadeMinutos ?> min</small>
                </div>
                <div>
                    <h2>Sessão criada — válida por <?= (int) $validadeMinutos ?> minuto<?= $validadeMinutos === 1 ? '' : 's' ?></h2>
                    <p>Copie o link abaixo e abra em <strong>outra janela anônima ou outro navegador</strong> com a conta do usuário. Depois volte a esta janela e registre o descarte.</p>
                    <p class="station-aviso">Esta janela está logada como administrador: o app redireciona contas admin para o painel, por isso a associação precisa ser feita separadamente.</p>
                    <div class="station-link">
                        <input id="station-link-qr" type="text" readonly value="<?= htmlspecialchars($linkQr, ENT_QUOTES, 'UTF-8') ?>" aria-label="Link de associação da sessão">
                        <button class="botao botao-primario" type="button" data-copiar="#station-link-qr">Copiar link</button>
                    </div>
                    <code>Token: <?= htmlspecialchars(substr((string) $sessaoCriada['token'], 0, 18), ENT_QUOTES, 'UTF-8') ?>...</code>
                </div>
            </section>
        <?php endif; ?>

        <section class="station-operacao">
            <div>
                <h2>Registrar descarte</h2>
                <p>Depois que o usuário confirmar a associação no app, selecione a sessão e registre o material e a quantidade.</p>
            </div>

            <form class="station-grid-form" method="post">
                <?= Csrf::campoHtml() ?>
                <input type="hidden" name="acao" value="registrar_descarte">

                <label>
                    Sessão
                    <select name="sessao_id" required>
                        <?php foreach ($sessoes as $sessao): ?>
                            <option value="<?= (int) $sessao['id'] ?>">
                                <?= htmlspecialchars($sessao['usuario'] . ' - ' . $sessao['estacao'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Material
                    <select name="material_id" required>
                        <?php foreach ($materiais as $material): ?>
                            <option value="<?= (int) $material['id'] ?>">
                                <?= htmlspecialchars($material['nome'] . ' (' . $material['unidade'] . ')', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Quantidade
                    <input type="number" name="quantidade" min="0.001" step="0.001" placeholder="0,750" required>
                </label>

                <button class="botao station-botao-principal" type="submit" <?= empty($sessoes) ? 'disabled' : '' ?>>Registrar descarte</button>
            </form>
        </section>
    </main>
    <script src="<?= Url::escapar('/frontend/assets/js/station.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/station.js') ?>" defer></script>
</body>
</html>
