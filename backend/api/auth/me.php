<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::iniciarSessao();

$usuario = Auth::usuarioLogado();

if ($usuario === null) {
    Response::erro('Sessão não autenticada.', 401);
}

Response::sucesso(['usuario' => $usuario]);
