<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/EcoDropRepository.php';

Auth::iniciarSessao();

$repo = new EcoDropRepository();
$repo->prepararDemo();

$mensagem = null;
$tipoMensagem = 'sucesso';
$sessaoCriada = null;

if (($_GET['associado'] ?? '') === '1') {
    $mensagem = 'Sessao conectada. Agora selecione a sessao em uso, informe material e quantidade para registrar o descarte.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $mensagem = 'Token de segurança inválido. Recarregue a página.';
        $tipoMensagem = 'erro';
    } elseif (($_POST['acao'] ?? '') === 'criar_sessao') {
        $sessaoCriada = $repo->criarSessaoEstacao((int) ($_POST['estacao_id'] ?? 0));
        $mensagem = 'Sessão temporária criada. Use o link/QR no app do usuário.';
    } elseif (($_POST['acao'] ?? '') === 'registrar_descarte') {
        $resultado = $repo->registrarDescarte(
            (string) ($_POST['token'] ?? ''),
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
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estação Demo - EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/station.css') ?>">
</head>
<body class="pagina-station-demo">
    <main class="station-shell">
        <header class="station-topo">
            <a class="station-marca eco-marca eco-marca--clara" href="<?= Url::escapar('/app/inicio/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
        </header>

        <section class="station-hero">
            <div>
                <p class="station-etiqueta">Totem fictício do MVP</p>
                <h1>Estação de descarte simulado</h1>
                <p>Gere uma sessão temporária, associe pelo app e registre material e quantidade como se viessem do hardware.</p>
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
                <button class="botao station-botao-principal" type="submit">Gerar QR temporário</button>
            </form>
        </section>

        <?php if ($mensagem !== null): ?>
            <div class="alerta alerta-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>" role="status">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($sessaoCriada): ?>
            <?php $linkQr = Url::para('/app/inicio/?qr=' . urlencode((string) $sessaoCriada['token'])); ?>
            <section class="station-qr">
                <div class="qr-falso" aria-label="QR Code fictício">
                    <?php for ($i = 0; $i < 49; $i++): ?>
                        <span class="<?= ($i + ord($sessaoCriada['token'][$i % 64])) % 3 === 0 ? 'preto' : '' ?>"></span>
                    <?php endfor; ?>
                </div>
                <div>
                    <h2>QR ativo por 2 minutos</h2>
                    <p>Como estamos sem câmera/leitor real, clique no link abaixo estando logado como usuário.</p>
                    <a class="botao botao-primario" href="<?= htmlspecialchars($linkQr, ENT_QUOTES, 'UTF-8') ?>">Associar no app</a>
                    <code><?= htmlspecialchars(substr((string) $sessaoCriada['token'], 0, 18), ENT_QUOTES, 'UTF-8') ?>...</code>
                </div>
            </section>
        <?php endif; ?>

        <section class="station-operacao">
            <div>
                <h2>Sessões em uso</h2>
                <p>Depois que o usuário associa o QR, selecione a sessão e simule o descarte.</p>
            </div>

            <form class="station-grid-form" method="post">
                <?= Csrf::campoHtml() ?>
                <input type="hidden" name="acao" value="registrar_descarte">

                <label>
                    Sessão
                    <select name="token" required>
                        <?php foreach ($sessoes as $sessao): ?>
                            <option value="<?= htmlspecialchars($sessao['token'], ENT_QUOTES, 'UTF-8') ?>">
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
</body>
</html>
