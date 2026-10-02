<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/GoogleOAuth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/services/AuthService.php';

Auth::iniciarSessao();

if (isset($_GET['error'])) {
    $_SESSION['google_oauth_erro'] = 'O login com Google foi cancelado ou não foi autorizado.';
    header('Location: ' . Url::para('/app/login/'));
    exit;
}

$estadoRecebido = (string) ($_GET['state'] ?? '');
$estadoEsperado = $_SESSION['google_oauth_state'] ?? '';

if ($estadoRecebido === '' || $estadoEsperado === '' || !hash_equals($estadoEsperado, $estadoRecebido)) {
    $_SESSION['google_oauth_erro'] = 'Estado de autenticação inválido. Tente novamente.';
    header('Location: ' . Url::para('/app/login/'));
    exit;
}

$codigo = (string) ($_GET['code'] ?? '');
if ($codigo === '') {
    $_SESSION['google_oauth_erro'] = 'Código de autorização ausente no retorno do Google.';
    header('Location: ' . Url::para('/app/login/'));
    exit;
}

try {
    $cliente = GoogleOAuth::cliente();
    $token = $cliente->fetchAccessTokenWithAuthCode($codigo);

    if (isset($token['error'])) {
        throw new RuntimeException((string) $token['error']);
    }

    $cliente->setAccessToken($token);
    $claims = $cliente->verifyIdToken((string) ($token['id_token'] ?? ''));
    if (!is_array($claims)) {
        throw new RuntimeException('ID token Google inválido.');
    }

    $googleSub = trim((string) ($claims['sub'] ?? ''));
    $emailGoogle = mb_strtolower(trim((string) ($claims['email'] ?? '')));
    $nomeGoogle = trim((string) ($claims['name'] ?? ''));
    $emailVerificado = ($claims['email_verified'] ?? false) === true;

    if (
        $googleSub === ''
        || mb_strlen($googleSub) > 255
        || filter_var($emailGoogle, FILTER_VALIDATE_EMAIL) === false
        || mb_strlen($emailGoogle) > 190
        || !$emailVerificado
    ) {
        throw new RuntimeException('Perfil Google incompleto ou sem email verificado.');
    }

    unset($_SESSION['google_oauth_state']);

    $authService = new AuthService();
    $resultadoGoogle = $authService->autenticarGoogle($googleSub);

    if ($resultadoGoogle['sucesso']) {
        unset($_SESSION['google_onboarding'], $_SESSION['google_user']);
        Auth::autenticar($resultadoGoogle['usuario']);
        $destino = $resultadoGoogle['usuario']['papel'] === 'admin'
            ? Url::para('/admin/dashboard/')
            : Url::para('/app/inicio/');
        header('Location: ' . $destino);
        exit;
    }

    if ($resultadoGoogle['encontrado']) {
        throw new RuntimeException($resultadoGoogle['mensagem'] ?? 'Conta EcoDrop indisponível.');
    }

    $contaPorEmail = $authService->buscarPorEmail($emailGoogle);
    if ($contaPorEmail !== null && $contaPorEmail['google_sub'] !== null) {
        throw new RuntimeException('A conta EcoDrop já está vinculada a outra identidade Google.');
    }

    $_SESSION['google_onboarding'] = [
        'google_sub' => $googleSub,
        'nome' => $nomeGoogle,
        'email' => $emailGoogle,
        'email_verified' => true,
        'criado_em' => time(),
    ];
    unset($_SESSION['google_user']);

    header('Location: ' . Url::para('/app/cadastro/google/'));
    exit;
} catch (Throwable $erro) {
    error_log('[EcoDrop] Google callback processing failed (' . get_class($erro) . ').');
    $_SESSION['google_oauth_erro'] = 'Não foi possível concluir o login com Google. Tente novamente.';
    header('Location: ' . Url::para('/app/login/'));
    exit;
}
