<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Request.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Url.php';
require_once __DIR__ . '/../../services/AuthService.php';
require_once __DIR__ . '/../../services/RateLimiter.php';

Auth::iniciarSessao();

if (Request::metodo() !== 'POST') {
    Response::erro('Método não permitido.', 405);
}

if (!Csrf::validar(Request::texto('csrf_token'))) {
    Response::erro('Token de segurança inválido. Recarregue a página e tente novamente.', 403);
}

$email = Request::texto('email');
$senha = Request::texto('senha', '', false);
// Indica se o login está sendo feito pela tela administrativa (RNF004: controle de acesso).
$contextoAdmin = Request::texto('contexto') === 'admin';

// Reserva tentativas por e-mail + IP de forma atômica para dificultar força bruta (5 em 15 minutos).
$limitador = new RateLimiter();
$chaveLimite = mb_strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? '');
if (!$limitador->registrarSePermitido('login', $chaveLimite, 5, 900)) {
    Response::erro('Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.', 429);
}

$resultado = (new AuthService())->autenticar($email, $senha);

if (!$resultado['sucesso']) {
    Response::erro($resultado['mensagem'], 401);
}

if ($contextoAdmin && $resultado['usuario']['papel'] !== 'admin') {
    // Mensagem genérica: não confirma que as credenciais eram válidas para uma
    // conta comum (evita oráculo de credenciais na tela administrativa).
    Response::erro('E-mail ou senha inválidos.', 401);
}

$limitador->limpar('login', $chaveLimite);
Auth::autenticar($resultado['usuario']);

$destino = $resultado['usuario']['papel'] === 'admin' ? Url::para('/admin/dashboard/') : Url::para('/app/inicio/');

Response::sucesso([
    'usuario' => $resultado['usuario'],
    'redirecionar_para' => $destino,
]);
