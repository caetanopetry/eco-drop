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

$nome = Request::texto('nome');
$email = Request::texto('email');
$cpf = Request::texto('cpf');
$senha = Request::texto('senha', '', false);

// Limita tentativas de cadastro por IP (20/hora) para conter automação concorrente.
$limitador = new RateLimiter();
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!$limitador->registrarSePermitido('registro_ip', $ip, 20, 3600)) {
    Response::erro('Muitas contas criadas recentemente. Aguarde e tente novamente.', 429);
}

$resultado = (new AuthService())->registrar($nome, $email, $cpf, $senha);

if (!$resultado['sucesso']) {
    Response::erro($resultado['mensagem'], 422, $resultado['campos'] ?? []);
}

Auth::autenticar($resultado['usuario']);

Response::sucesso([
    'usuario' => $resultado['usuario'],
    'redirecionar_para' => Url::para('/app/inicio/'),
], 201);
