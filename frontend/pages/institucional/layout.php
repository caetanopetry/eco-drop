<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Url.php';

$paginas = [
    'sobre' => ['Sobre o EcoDrop', 'Uma ideia que ganha novos ciclos', 'Pequenas atitudes. Um futuro com mais possibilidades.', 'Conheça o EcoDrop e nossa proposta de aproximar a coleta seletiva do dia a dia, com informação e incentivo.'],
    'contato' => ['Contato', 'Perto de você', 'Todo novo ciclo começa com uma conversa.', 'Fale com o EcoDrop. Encontre nossos canais de contato e compartilhe suas dúvidas, ideias e propostas de parceria.'],
    'perguntas-frequentes' => ['Perguntas frequentes', 'Pode perguntar', 'Para começar seu ciclo com mais clareza.', 'Tire suas dúvidas sobre cadastro, materiais, pontos e a versão demonstrativa do EcoDrop.'],
    'termos-de-uso' => ['Termos de uso', 'Combinados para um bom ciclo', 'Transparência em cada etapa.', 'Conheça as condições de uso da plataforma EcoDrop e os limites da versão demonstrativa.'],
    'politica-de-privacidade' => ['Política de privacidade', 'Cuidado também com seus dados', 'Sua confiança faz parte do ciclo.', 'Saiba quais dados o EcoDrop utiliza, para que servem e como funcionam os recursos de privacidade nesta versão.'],
];

if (!isset($paginas[$paginaInstitucional ?? ''])) {
    http_response_code(404);
    exit('Página não encontrada.');
}
[$titulo, $sobretitulo, $chamada, $descricao] = $paginas[$paginaInstitucional];
$emailContato = trim((string) Config::get('contato.email', ''));
$emailContato = filter_var($emailContato, FILTER_VALIDATE_EMAIL) ? $emailContato : '';
$escapar = static fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#173f32">
  <meta name="description" content="<?= $escapar($descricao) ?>">
  <title><?= $escapar($titulo) ?> — EcoDrop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php foreach (['variables', 'global', 'components', 'site', 'institucional', 'hover-footer'] as $estilo): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $estilo . '.css') ?>">
  <?php endforeach; ?>
  <?php if ($paginaInstitucional === 'contato'): ?><link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/contato.css') ?>"><?php endif; ?>
  <script src="<?= Url::escapar('/frontend/assets/js/hover-footer.js') ?>" defer></script>
  <script src="<?= Url::escapar('/frontend/assets/js/site.js') ?>" defer></script>
</head>
<body class="pagina-site pagina-institucional pagina-<?= $escapar($paginaInstitucional) ?>">
  <a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>
  <?php require __DIR__ . '/../../components/navbar/navbar-site.php'; ?>
  <main id="conteudo">
    <section class="institucional-hero">
      <div class="container">
        <nav class="caminho-pagina" aria-label="Você está aqui"><a href="<?= Url::escapar('/') ?>">Início</a><span aria-hidden="true">/</span><span aria-current="page"><?= $escapar($titulo) ?></span></nav>
        <div class="institucional-hero-grade">
          <div class="institucional-hero-texto">
            <span class="sobretitulo"><?= $escapar($sobretitulo) ?></span>
            <h1><?= $escapar($titulo) ?></h1>
            <p class="institucional-chamada"><?= $escapar($chamada) ?></p>
            <?php if ($paginaInstitucional !== 'contato'): ?><p><?= $escapar($descricao) ?></p><?php endif; ?>
          </div>
          <div class="institucional-emblema" aria-hidden="true"><span class="emblema-flor">✳</span><span class="emblema-miolo"><?= $paginaInstitucional === 'perguntas-frequentes' ? '?' : ($paginaInstitucional === 'contato' ? 'oi!' : '↻') ?></span><span class="emblema-legenda">BONS CICLOS COMEÇAM AQUI</span></div>
        </div>
      </div>
    </section>
    <nav class="institucional-navegacao" aria-label="Explore o EcoDrop"><div class="container">
      <?php foreach (['sobre' => 'Nossa ideia', 'contato' => 'Vamos conversar', 'perguntas-frequentes' => 'Tire suas dúvidas', 'termos-de-uso' => 'Termos de uso', 'politica-de-privacidade' => 'Privacidade'] as $rota => $rotulo): ?>
        <a href="<?= Url::escapar('/' . $rota . '/') ?>"<?= $paginaInstitucional === $rota ? ' aria-current="page"' : '' ?>><?= $escapar($rotulo) ?></a>
      <?php endforeach; ?>
    </div></nav>
    <div class="container institucional-conteudo">
      <?php require __DIR__ . '/' . $paginaInstitucional . '.php'; ?>
    </div>
  </main>
  <?php require __DIR__ . '/../../components/footer/hover-footer.php'; ?>
</body>
</html>
