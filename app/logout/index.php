<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/core/Auth.php';
require_once __DIR__ . '/../../backend/core/Csrf.php';
require_once __DIR__ . '/../../backend/core/Url.php';

Auth::iniciarSessao();

// Encerrar sessão exige POST com token CSRF: um link/imagem externos não
// conseguem deslogar a vítima (o token não é conhecido por terceiros).
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || !Csrf::validar(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    header('Location: ' . Url::para('/'));
    exit;
}

Auth::encerrarSessao();
header('Location: ' . Url::para('/'));
exit;
