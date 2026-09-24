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

$email = Request::texto('email');
$senha = Request::texto('senha');
// Indica se o login está sendo feito pela tela administrativa (RNF004: controle de acesso).
$contextoAdmin = Request::texto('contexto') === 'admin';

$resultado = (new AuthService())->autenticar($email, $senha);

if (!$resultado['sucesso']) {
    Response::erro($resultado['mensagem'], 401);
}

if ($contextoAdmin && $resultado['usuario']['papel'] !== 'admin') {
    Response::erro('Esta conta não possui acesso administrativo.', 403);
}

Auth::autenticar($resultado['usuario']);

$destino = $resultado['usuario']['papel'] === 'admin' ? Url::para('/admin/dashboard/') : Url::para('/app/inicio/');

Response::sucesso([
    'usuario' => $resultado['usuario'],
    'redirecionar_para' => $destino,
]);
