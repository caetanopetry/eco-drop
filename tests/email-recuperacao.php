<?php

declare(strict_types=1);

// Testa apenas a montagem do e-mail; não envia nada e não acessa o banco.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../backend/services/EmailTemplate.php';

function conferirEmail(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

$link = 'https://ecodrop.example/app/redefinir-senha/?token=abc123';
$base = 'https://ecodrop.example';
$conteudo = EmailTemplate::recuperacaoSenha('Maria <script>alert(1)</script> da Silva', $link, $base, 'logo-ecodrop');

conferirEmail($conteudo['assunto'] === 'Redefinição de senha — EcoDrop', 'Assunto inesperado.');
conferirEmail(str_contains($conteudo['html'], 'cid:logo-ecodrop'), 'Logo embutido ausente.');
conferirEmail(str_contains($conteudo['html'], 'Olá, Maria,'), 'Saudação personalizada ausente.');
conferirEmail(!str_contains($conteudo['html'], '<script>'), 'Nome não foi escapado no HTML.');
conferirEmail(str_contains($conteudo['html'], htmlspecialchars($link, ENT_QUOTES, 'UTF-8')), 'Link do botão ausente.');
conferirEmail(str_contains($conteudo['html'], '15 minutos'), 'Aviso de expiração ausente.');
$linkSemEsquema = preg_replace('#^https?://#i', '', $link);
conferirEmail(str_contains($conteudo['html'], '>' . $linkSemEsquema . '</a>'), 'Link exibido deveria estar sem o esquema http(s).');
conferirEmail(str_contains($conteudo['html'], 'href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"'), 'O href deveria continuar com a URL completa.');
conferirEmail(str_contains($conteudo['html'], $base . '/politica-de-privacidade/'), 'Link de privacidade ausente.');
conferirEmail(!str_contains($conteudo['html'], '{$'), 'Placeholder não substituído no HTML.');
conferirEmail(str_contains($conteudo['texto'], $link), 'Link ausente na versão texto.');
conferirEmail(!str_contains($conteudo['texto'], '<'), 'Versão texto contém marcação HTML.');

$semLogo = EmailTemplate::recuperacaoSenha('Ana', $link, $base);
conferirEmail(!str_contains($semLogo['html'], 'cid:'), 'CID indevido sem logo.');
conferirEmail(str_contains($semLogo['html'], 'EcoDrop'), 'Fallback textual da marca ausente.');
conferirEmail(str_contains($semLogo['texto'], 'Olá, Ana'), 'Saudação ausente na versão texto.');

echo "OK: template de recuperação de senha validado (personalização, escape, link, expiração, privacidade e versão texto).\n";
