<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Url.php';

$paginas = [
    'sobre' => ['Sobre o EcoDrop', 'Conheça o EcoDrop e nossa proposta de aproximar a coleta seletiva do dia a dia, com informação e incentivo.'],
    'contato' => ['Contato', 'Fale com o EcoDrop. Encontre nossos canais de contato e compartilhe suas dúvidas, ideias e propostas de parceria.'],
    'perguntas-frequentes' => ['Perguntas frequentes', 'Tire suas dúvidas sobre cadastro, materiais, pontos e a versão demonstrativa do EcoDrop.'],
    'termos-de-uso' => ['Termos de uso', 'Conheça as condições de uso da plataforma EcoDrop e os limites da versão demonstrativa.'],
    'politica-de-privacidade' => ['Política de privacidade', 'Saiba quais dados o EcoDrop utiliza, para que servem e como funcionam os recursos de privacidade nesta versão.'],
];

if (!isset($paginas[$paginaInstitucional ?? ''])) {
    http_response_code(404);
    exit('Página não encontrada.');
}
[$titulo, $descricao] = $paginas[$paginaInstitucional];
$emailContato = trim((string) Config::get('contato.email', ''));
$emailContato = filter_var($emailContato, FILTER_VALIDATE_EMAIL) ? $emailContato : '';
$escapar = static fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
  <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#173f32">
  <meta name="description" content="<?= $escapar($descricao) ?>">
  <title><?= $escapar($titulo) ?> — EcoDrop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php foreach (['variables', 'global', 'components', 'site', 'institucional', 'hover-footer'] as $estilo): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $estilo . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $estilo . '.css') ?>">
  <?php endforeach; ?>
  <?php if ($paginaInstitucional === 'contato'): ?><link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/contato.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/contato.css') ?>"><?php endif; ?>
  <script src="<?= Url::escapar('/frontend/assets/js/hover-footer.js') ?>" defer></script>
  <script src="<?= Url::escapar('/frontend/assets/js/site.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body class="pagina-site">
  <a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>
  <?php require __DIR__ . '/../../components/navbar/navbar-site.php'; ?>
  <main id="conteudo">
    <header class="container institucional-cabecalho">
      <nav class="caminho-pagina" aria-label="Você está aqui"><a href="<?= Url::escapar('/') ?>">Início</a><span aria-hidden="true">/</span><span aria-current="page"><?= $escapar($titulo) ?></span></nav>
      <h1><?= $escapar($titulo) ?></h1>
    </header>
    <div class="container institucional-conteudo">
      <?php require __DIR__ . '/' . $paginaInstitucional . '.php'; ?>
    </div>
  </main>
  <?php require __DIR__ . '/../../components/footer/hover-footer.php'; ?>
</body>
</html>
