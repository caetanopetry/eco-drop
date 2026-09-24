<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/core/Auth.php';
require_once __DIR__ . '/../../backend/core/Url.php';
Auth::iniciarSessao();
Auth::encerrarSessao();
header('Location: ' . Url::para('/'));
exit;
