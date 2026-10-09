<?php require_once __DIR__ . '/../../../backend/core/Url.php'; ?>
<header class="site-cabecalho">
  <div class="container flex itens-centro justificar-entre">
    <a class="eco-marca" href="<?= Url::escapar('/') ?>" aria-label="EcoDrop, página inicial"><?php require __DIR__ . '/../brand/marca.php'; ?></a>
    <?php require __DIR__ . '/../theme/toggle.php'; ?>
    <button class="site-menu-botao" type="button" aria-expanded="false" aria-controls="site-menu" hidden>
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      <span>Menu</span>
    </button>
    <nav id="site-menu" aria-label="Navegação principal" class="flex itens-centro gap-4">
      <a href="<?= Url::escapar('/') ?>#como-funciona">Como funciona</a>
      <a href="<?= Url::escapar('/') ?>#materiais">Materiais</a>
      <a href="<?= Url::escapar('/sobre/') ?>"<?= ($paginaInstitucional ?? '') === 'sobre' ? ' aria-current="page"' : '' ?>>Sobre</a>
      <a href="<?= Url::escapar('/contato/') ?>"<?= ($paginaInstitucional ?? '') === 'contato' ? ' aria-current="page"' : '' ?>>Contato</a>
      <a href="<?= Url::escapar('/app/login/') ?>">Entrar</a>
      <a href="<?= Url::escapar('/app/cadastro/') ?>" class="botao botao-primario">Criar conta</a>
    </nav>
  </div>
</header>
<script src="<?= Url::escapar('/frontend/assets/js/site-menu.js') ?>" defer></script>
