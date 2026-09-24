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

$nome = Request::texto('nome');
$email = Request::texto('email');
$cpf = Request::texto('cpf');
$senha = Request::texto('senha');

$resultado = (new AuthService())->registrar($nome, $email, $cpf, $senha);

if (!$resultado['sucesso']) {
    Response::erro($resultado['mensagem'], 422, $resultado['campos'] ?? []);
}

Auth::autenticar($resultado['usuario']);

Response::sucesso([
    'usuario' => $resultado['usuario'],
    'redirecionar_para' => Url::para('/app/inicio/'),
], 201);
