<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::iniciarSessao();
Auth::encerrarSessao();

Response::sucesso(['redirecionar_para' => '/']);
