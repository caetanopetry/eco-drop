<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/services/AuthService.php';

Auth::iniciarSessao();

$usuarioLogado = Auth::usuarioLogado();
if ($usuarioLogado !== null) {
    unset($_SESSION['google_onboarding']);
    $destino = $usuarioLogado['papel'] === 'admin'
        ? Url::para('/admin/dashboard/')
        : Url::para('/app/inicio/');
    header('Location: ' . $destino);
    exit;
}

$googlePendente = $_SESSION['google_onboarding'] ?? null;
$agora = time();
$criadoEm = is_array($googlePendente) ? (int) ($googlePendente['criado_em'] ?? 0) : 0;
$emailGoogle = is_array($googlePendente) ? (string) ($googlePendente['email'] ?? '') : '';
$googleSub = is_array($googlePendente) ? (string) ($googlePendente['google_sub'] ?? '') : '';

if (
    !is_array($googlePendente)
    || ($googlePendente['email_verified'] ?? false) !== true
    || $googleSub === ''
    || mb_strlen($googleSub) > 255
    || filter_var($emailGoogle, FILTER_VALIDATE_EMAIL) === false
    || mb_strlen($emailGoogle) > 190
    || $criadoEm <= 0
    || $agora - $criadoEm < 0
    || $agora - $criadoEm > 900
) {
    unset($_SESSION['google_onboarding']);
    header('Location: ' . Url::para('/app/login/'));
    exit;
}

$authService = new AuthService();
$contaExistente = $authService->buscarPorEmail($emailGoogle);

if ($contaExistente !== null && $contaExistente['google_sub'] !== null) {
    unset($_SESSION['google_onboarding']);
    header('Location: ' . Url::para('/app/login/'));
    exit;
}

$nomeGoogle = trim((string) ($googlePendente['nome'] ?? ''));
$vinculacao = $contaExistente !== null;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Concluir cadastro — EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/auth.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/auth.css') ?>-<?= filesize(__DIR__ . '/../../assets/css/auth.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body>
    <div class="tema-acesso"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?></div>
    <div class="auth-troca auth-pronta">
        <main class="auth-formulario">
            <a class="eco-marca auth-marca-mobile" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
            <section class="auth-conteudo" aria-label="Concluir cadastro">
                <h1 tabindex="-1"><?= $vinculacao ? 'Vincular sua conta' : 'Concluir cadastro' ?></h1>
                <?php if ($vinculacao): ?>
                    <p class="subtitulo texto-suave">Já existe uma conta EcoDrop com este e-mail. Confirme sua senha atual para vincular sua conta Google com segurança.</p>
                <?php else: ?>
                    <p class="subtitulo texto-suave">Complete os dados da sua conta EcoDrop. O CPF e a senha continuam protegidos pelas mesmas regras do cadastro tradicional.</p>
                <?php endif; ?>

                <?php $id = 'feedback-google'; include __DIR__ . '/../../components/alerts/alert.php'; ?>

                <form data-formulario="google-completar">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="<?= $vinculacao ? 'vincular' : 'cadastrar' ?>">

                    <?php if (!$vinculacao): ?>
                        <p class="campo-ajuda">Nome da conta Google: <strong><?= htmlspecialchars($nomeGoogle, ENT_QUOTES, 'UTF-8') ?></strong></p>
                    <?php else: ?>
                        <p class="campo-ajuda">Conta Google: <?= htmlspecialchars($emailGoogle, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <?php if (!$vinculacao): ?>
                        <div class="campo" data-campo="cpf">
                            <label for="google-cpf">CPF</label>
                            <input type="text" id="google-cpf" name="cpf" required inputmode="numeric" maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" placeholder="000.000.000-00" title="Informe um CPF no formato 000.000.000-00" autocomplete="off">
                            <span class="campo-erro-texto" data-erro-campo></span>
                        </div>

                        <div class="campo" data-campo="senha">
                            <label for="google-senha">Crie uma senha EcoDrop</label>
                            <div class="campo-senha-controle">
                                <input type="password" id="google-senha" name="senha" required minlength="8" autocomplete="new-password" aria-describedby="google-senha-ajuda">
                                <button type="button" class="alternar-senha" data-toggle-senha data-visivel="false" aria-controls="google-senha" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">
                                    <svg class="icone-olho--aberto" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="icone-olho--fechado" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a13.8 13.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3-.4"/></svg>
                                </button>
                            </div>
                            <span class="campo-ajuda" id="google-senha-ajuda">Mínimo de 8 caracteres.</span>
                            <span class="campo-erro-texto" data-erro-campo></span>
                        </div>

                        <div class="campo" data-campo="confirmar_senha">
                            <label for="google-confirmar-senha">Confirme sua senha EcoDrop</label>
                            <input type="password" id="google-confirmar-senha" name="confirmar_senha" required minlength="8" autocomplete="new-password">
                            <span class="campo-erro-texto" data-erro-campo></span>
                        </div>

                    <?php else: ?>
                        <div class="campo" data-campo="senha">
                            <label for="google-senha">Senha atual da conta EcoDrop</label>
                            <div class="campo-senha-controle">
                                <input type="password" id="google-senha" name="senha" required autocomplete="current-password">
                                <button type="button" class="alternar-senha" data-toggle-senha data-visivel="false" aria-controls="google-senha" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">
                                    <svg class="icone-olho--aberto" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="icone-olho--fechado" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a13.8 13.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3-.4"/></svg>
                                </button>
                            </div>
                            <span class="campo-erro-texto" data-erro-campo></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!$vinculacao): ?>
                        <p class="campo-ajuda auth-termos">
                            E-mail verificado pelo Google: <strong><?= htmlspecialchars($emailGoogle, ENT_QUOTES, 'UTF-8') ?></strong>.
                            Consulte a <a href="<?= Url::escapar('/politica-de-privacidade/') ?>" target="_blank" rel="noopener">Política de privacidade</a>
                            e os <a href="<?= Url::escapar('/termos-de-uso/') ?>" target="_blank" rel="noopener">Termos de uso</a>.
                        </p>
                    <?php endif; ?>
                    <button type="submit" class="botao botao-primario botao-bloco"><?= $vinculacao ? 'Confirmar e vincular conta' : 'Criar conta EcoDrop' ?></button>
                </form>

                <p class="rodape-autenticacao">Não quer continuar? <a href="<?= Url::escapar('/app/login/') ?>">Voltar ao login</a></p>
                <a class="auth-voltar" href="<?= Url::escapar('/') ?>">← Voltar ao início</a>
            </section>
        </main>
        <aside class="auth-marca-painel" aria-label="EcoDrop">
            <a class="eco-marca eco-marca--clara" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
        </aside>
    </div>
    <script>
        window.EcoDropBaseUrl = <?= json_encode(Url::para('/'), JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= Url::escapar('/frontend/assets/js/api.js') ?>"></script>
    <script src="<?= Url::escapar('/frontend/assets/js/auth.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/auth.js') ?>-<?= filesize(__DIR__ . '/../../assets/js/auth.js') ?>"></script>
</body>
</html>
