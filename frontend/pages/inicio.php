<?php require_once __DIR__ . '/../../backend/core/Url.php'; ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../components/brand/favicon.php'; ?>
  <meta charset="utf-8">
    <?php require __DIR__ . '/../components/theme/head.php'; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#173f32">
  <meta name="description" content="Conheça o EcoDrop: separe materiais, acompanhe pontos e explore resgates em ambiente de demonstração.">
  <title>EcoDrop — reciclagem, pontos e recompensas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/global.css') ?>">
  <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/site.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/site.css') ?>">
  <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/hover-footer.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/hover-footer.css') ?>">
  <script src="<?= Url::escapar('/frontend/assets/js/hover-footer.js') ?>" defer></script>
  <script src="<?= Url::escapar('/frontend/assets/js/site.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/theme.css') ?>">
</head>
<body class="pagina-site">
  <a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>
  <?php require __DIR__ . '/../components/navbar/navbar-site.php'; ?>
  <main id="conteudo">
    <section class="hero">
      <div class="container hero-grade">
        <div class="hero-texto">
          <span class="sobretitulo">Recicle. Acumule. Troque.</span>
          <h1>Recicle seus materiais. <em>Acompanhe seus pontos.</em></h1>
          <p>No EcoDrop, a conta é associada à estação por uma sessão temporária. Os descartes validados geram pontos para acompanhar no painel.</p>
          <div class="hero-acoes"><a class="botao botao-primario" href="<?= Url::escapar('/app/cadastro/') ?>">Quero começar</a><a class="botao botao-secundario" href="#como-funciona">Como funciona</a></div>
          <p class="hero-nota">Ambiente de demonstração: estações, pontos e recompensas são simulados.</p>
        </div>
        <div class="hero-ilustracao">
          <img src="<?= Url::escapar('/frontend/assets/img/ciclo.svg') ?>" width="600" height="560" alt="Garrafas e papel em uma cesta de reciclagem, cercada por folhas e um selo: menos lixo, mais vida.">
        </div>
      </div>
    </section>
    <section class="secao-estatisticas" aria-labelledby="titulo-estatisticas">
      <div class="container">
        <div class="secao-cabecalho"><div><span class="sobretitulo">Brasil em números</span><h2 id="titulo-estatisticas">O desafio dos resíduos, em dados.</h2></div><p>Estimativas nacionais de resíduos urbanos e um índice de reciclagem mecânica de plásticos. Cada indicador tem seu próprio escopo.</p></div>
        <dl class="estatisticas-grade">
          <div class="estatistica-card estatistica-card--geracao"><dt>RSU gerados <span>BRASIL · 2024</span></dt><dd>81,6 <small>mi t</small></dd><p class="estatistica-descricao">Estimativa anual de resíduos urbanos.</p><div class="estatistica-link"><a href="https://www.abrema.org.br/download/98191/" target="_blank" rel="noopener noreferrer">Fonte: ABREMA <span aria-hidden="true">↗</span><span class="sr-only"> (abre em nova aba)</span></a></div></div>
          <div class="estatistica-card estatistica-card--coleta"><dt>RSU coletados <span>BRASIL · 2024</span></dt><dd>93,7<small>%</small></dd><p class="estatistica-descricao">Dos resíduos urbanos gerados.</p><div class="estatistica-link"><a href="https://www.abrema.org.br/download/98191/" target="_blank" rel="noopener noreferrer">Fonte: ABREMA <span aria-hidden="true">↗</span><span class="sr-only"> (abre em nova aba)</span></a></div></div>
          <div class="estatistica-card estatistica-card--destinacao"><dt>Destinação inadequada <span>BRASIL · 2024</span></dt><dd>40,3<small>%</small></dd><p class="estatistica-descricao">Do volume destinado: 28,1 mi t.</p><div class="estatistica-link"><a href="https://www.abrema.org.br/download/98191/" target="_blank" rel="noopener noreferrer">Fonte: ABREMA <span aria-hidden="true">↗</span><span class="sr-only"> (abre em nova aba)</span></a></div></div>
          <div class="estatistica-card estatistica-card--plastico"><dt>Reciclagem de plásticos <span>BRASIL · 2025</span></dt><dd>24,2<small>%</small></dd><p class="estatistica-descricao">Embalagens e itens equiparáveis.</p><div class="estatistica-link"><a href="https://www.abiplast.org.br/noticias/setor-de-reciclagem-mecanica-de-plasticos-recupera-producao-faturamento-e-empregos-em-2025/" target="_blank" rel="noopener noreferrer">Fonte: ABIPLAST · PICPlast <span aria-hidden="true">↗</span><span class="sr-only"> (abre em nova aba)</span></a></div></div>
        </dl>
      </div>
    </section>
    <section id="como-funciona" class="secao-timeline">
      <div class="container">
        <div class="secao-cabecalho"><div><span class="sobretitulo">Como funciona</span><h2>Do descarte ao painel.<br>Em três passos.</h2></div><p>Veja como a demonstração associa o descarte à conta e registra os pontos.</p></div>
        <ol class="timeline" data-revelar>
          <li><span class="passo-icone" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span><div><strong>Separe seus recicláveis</strong><p>Plástico, papel, vidro e óleo usado: conheça os materiais previstos para as estações EcoDrop.</p></div></li>
          <li><span class="passo-icone" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 9V3h6m6 0h6v6m0 6v6h-6m-6 0H3v-6M7 7h3v3H7zm7 0h3v3h-3zM7 14h3v3H7zm7 0h3v3h-3z"/></svg></span><div><strong>Acumule pontos</strong><p>Conecte sua conta à estação por QR Code. Cada descarte validado gera pontos para acompanhar no seu painel.</p></div></li>
          <li><span class="passo-icone" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 10h18v4H3zm2 4v7h14v-7M12 10v11m0-11C2 10 5 1 9 5l3 5Zm0 0c10 0 7-9 3-5l-3 5Z"/></svg></span><div><strong>Consulte seus resgates</strong><p>Use o saldo para explorar recompensas e vouchers disponíveis no painel.</p></div></li>
        </ol>
      </div>
    </section>
    <section id="materiais" class="secao-materiais">
      <div class="container">
        <div class="secao-cabecalho"><div><span class="sobretitulo">Comece com o que você já tem</span><h2>Seu próximo ponto<br>pode estar em casa.</h2></div><p>Conheça os materiais previstos no projeto e veja como preparar cada um para a coleta.</p></div>
        <ul class="lista-materiais" data-revelar>
          <li class="material material-plastico"><svg aria-hidden="true" viewBox="0 0 100 100"><path d="M39 8h22v13H39zm2 13v12L30 47v39q0 7 7 7h26q7 0 7-7V47L59 33V21M31 55h38M31 77h38M42 64h16"/></svg><h3>Plástico</h3><p>Embalagens limpas e separadas para a coleta.</p><details class="material-dica"><summary>Antes de separar</summary><p>Esvazie as embalagens e retire os restos de produto. Confira quais tipos são aceitos pela coleta da sua região.</p></details></li>
          <li class="material material-papel"><svg aria-hidden="true" viewBox="0 0 100 100"><path d="m24 17 49-7 10 72-49 7ZM17 24h49v69H17z" fill="#d8e5ea"/><path d="M27 37h29M27 45h29M27 76h29M27 83h19M27 54h29v14H27z"/></svg><h3>Papel</h3><p>Papéis secos e papelão separados dos resíduos orgânicos.</p><details class="material-dica"><summary>Antes de separar</summary><p>Mantenha papéis e papelão secos. Separe os materiais sujos de comida e consulte as orientações da coleta local.</p></details></li>
          <li class="material material-vidro"><svg aria-hidden="true" viewBox="0 0 100 100"><path d="M41 8h18v9H41zm2 9v24c0 9-13 12-13 23v23q0 6 6 6h28q6 0 6-6V64c0-11-13-14-13-23V17M30 64h40M30 81h40"/></svg><h3>Vidro</h3><p>Potes e garrafas vazios, preparados com cuidado.</p><details class="material-dica"><summary>Antes de separar</summary><p>Esvazie potes e garrafas. Para vidros quebrados, siga as instruções de embalagem e identificação da coleta local.</p></details></li>
          <li class="material material-oleo"><svg aria-hidden="true" viewBox="0 0 100 100"><path d="M35 9h24v12H35zm0 12-7 14v52q0 6 6 6h32q6 0 6-6V40L59 21M61 35h17v23h-6"/><path d="M50 43s-12 15-12 22a12 12 0 0 0 24 0c0-7-12-22-12-22Z"/></svg><h3>Óleo de cozinha</h3><p>Usado na cozinha. Longe do ralo.</p><details class="material-dica"><summary>Antes de separar</summary><p>Espere esfriar e guarde em uma garrafa bem fechada. Procure um ponto de coleta de óleo usado na sua região.</p></details></li>
        </ul>
      </div>
    </section>
    <section class="secao-mvp"><div class="container"><div class="convite"><div><span class="sobretitulo">Comece por aqui</span><h2>Conheça o fluxo<br>de descarte.</h2><p>Crie sua conta para acompanhar a simulação de descarte, pontos e resgates no EcoDrop.</p></div><a class="botao botao-primario" href="<?= Url::escapar('/app/cadastro/') ?>">Criar conta</a></div></div></section>
  </main>
  <?php require __DIR__ . '/../components/footer/hover-footer.php'; ?>
</body>
</html>
