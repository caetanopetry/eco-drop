<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/core/Auth.php';
require_once __DIR__ . '/../../backend/core/GoogleOAuth.php';
require_once __DIR__ . '/../../backend/core/Url.php';

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

unset($_SESSION['google_onboarding']);

$configuracao = GoogleOAuth::configuracao();
$clientId = $configuracao['client_id'];
$redirectUri = $configuracao['redirect_uri'];

if ($clientId === '' || $redirectUri === '') {
    $_SESSION['google_oauth_erro'] = 'Google OAuth ainda não foi configurado. Defina GOOGLE_CLIENT_ID e GOOGLE_REDIRECT_URI no .env.';
    header('Location: ' . Url::para('/app/login/'));
    exit;
}

try {
    $authUrl = GoogleOAuth::iniciarFluxo();
    header('Location: ' . $authUrl);
    exit;
} catch (Throwable $erro) {
    error_log('[EcoDrop] Falha ao iniciar OAuth do Google: ' . $erro->getMessage());
    $_SESSION['google_oauth_erro'] = 'Não foi possível iniciar o login com Google. Tente novamente.';
    header('Location: ' . Url::para('/app/login/'));
    exit;
}
