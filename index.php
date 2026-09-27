<?php

declare(strict_types=1);

require_once __DIR__ . '/backend/core/Url.php';

$paginasInstitucionais = [
    'sobre',
    'contato',
    'perguntas-frequentes',
    'termos-de-uso',
    'politica-de-privacidade',
];

$paginaInstitucional = isset($_GET['pagina']) && is_string($_GET['pagina'])
    ? strtolower(trim($_GET['pagina']))
    : '';

if (in_array($paginaInstitucional, $paginasInstitucionais, true)) {
    $caminhoAtual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $caminhoCanonico = Url::para('/' . $paginaInstitucional . '/');

    if ($caminhoAtual !== $caminhoCanonico) {
        header('Location: ' . $caminhoCanonico, true, 301);
        exit;
    }

    require __DIR__ . '/frontend/pages/institucional/layout.php';
    exit;
}

require __DIR__ . '/frontend/pages/inicio.html';
