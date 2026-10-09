<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Request.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Url.php';
require_once __DIR__ . '/../../services/AuthService.php';

Auth::iniciarSessao();

if (Request::metodo() !== 'POST') {
    Response::erro('Método não permitido.', 405);
}

if (!Csrf::validar(Request::texto('csrf_token'))) {
    Response::erro('Token de segurança inválido. Recarregue a página e tente novamente.', 419);
}

$pendente = $_SESSION['google_onboarding'] ?? null;
$criadoEm = is_array($pendente) ? (int) ($pendente['criado_em'] ?? 0) : 0;
$email = is_array($pendente) ? (string) ($pendente['email'] ?? '') : '';
$nomeGoogle = is_array($pendente) ? trim((string) ($pendente['nome'] ?? '')) : '';
$googleSub = is_array($pendente) ? (string) ($pendente['google_sub'] ?? '') : '';
$agora = time();

if (
    !is_array($pendente)
    || ($pendente['email_verified'] ?? false) !== true
    || $nomeGoogle === ''
    || mb_strlen($nomeGoogle) > 150
    || $googleSub === ''
    || mb_strlen($googleSub) > 255
    || filter_var($email, FILTER_VALIDATE_EMAIL) === false
    || mb_strlen($email) > 190
    || $criadoEm <= 0
    || $agora - $criadoEm < 0
    || $agora - $criadoEm > 900
) {
    unset($_SESSION['google_onboarding']);
    Response::erro('A etapa de cadastro Google expirou. Inicie o processo novamente.', 401);
}

if (Auth::usuarioLogado() !== null) {
    Response::erro('Encerre a sessão atual antes de concluir outra conta.', 409);
}

$authService = new AuthService();
$acao = Request::texto('acao');

if ($acao === 'cadastrar') {
    $senha = Request::texto('senha');
    $confirmarSenha = Request::texto('confirmar_senha');
    if ($confirmarSenha === '' || !hash_equals($senha, $confirmarSenha)) {
        Response::erro(
            'As senhas não conferem.',
            422,
            ['confirmar_senha' => 'Confirme a mesma senha informada acima.']
        );
    }

    $resultado = $authService->registrar(
        $nomeGoogle,
        $email,
        Request::texto('cpf'),
        $senha,
        $googleSub
    );

    if (!$resultado['sucesso']) {
        Response::erro(
            $resultado['mensagem'] ?? 'Verifique os dados informados.',
            422,
            $resultado['campos'] ?? []
        );
    }

    (new UsuarioRepository())->salvarFotoGoogle((int) $resultado['usuario']['id'], $pendente['foto_google'] ?? null);
    unset($_SESSION['google_onboarding']);
    Auth::autenticar($resultado['usuario']);

    $destino = $resultado['usuario']['papel'] === 'admin'
        ? Url::para('/admin/dashboard/')
        : Url::para('/app/inicio/');
    Response::sucesso([
        'usuario' => $resultado['usuario'],
        'redirecionar_para' => $destino,
    ], 201);
}

if ($acao === 'vincular') {
    $resultado = $authService->autenticar($email, Request::texto('senha'));
    if (!$resultado['sucesso']) {
        Response::erro($resultado['mensagem'], 401);
    }

    $conta = $authService->buscarPorEmail($email);
    if ($conta === null || $conta['google_sub'] !== null) {
        Response::erro('Não foi possível vincular esta conta Google. Verifique a conta EcoDrop.', 409);
    }

    if (!$authService->vincularGoogle((int) $resultado['usuario']['id'], $googleSub)) {
        Response::erro('Esta conta Google já está vinculada a outra conta EcoDrop.', 409);
    }

    (new UsuarioRepository())->salvarFotoGoogle((int) $resultado['usuario']['id'], $pendente['foto_google'] ?? null);
    unset($_SESSION['google_onboarding']);
    Auth::autenticar($resultado['usuario']);

    $destino = $resultado['usuario']['papel'] === 'admin'
        ? Url::para('/admin/dashboard/')
        : Url::para('/app/inicio/');
    Response::sucesso([
        'usuario' => $resultado['usuario'],
        'redirecionar_para' => $destino,
    ]);
}

Response::erro('Ação de cadastro Google inválida.', 400);
