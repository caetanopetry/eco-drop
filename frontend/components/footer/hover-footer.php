<?php require_once __DIR__ . '/../../../backend/core/Url.php'; ?>
<footer class="eco-footer" aria-label="Rodapé EcoDrop">
  <div class="eco-footer__inner">
    <div class="eco-footer__grid">
      <div class="eco-footer__brand">
        <a class="eco-footer__logo eco-marca eco-marca--clara" href="<?= Url::escapar('/') ?>" aria-label="EcoDrop, página inicial">
          <?php require __DIR__ . '/../brand/marca.php'; ?>
        </a>
      </div>

      <nav class="eco-footer__nav" aria-labelledby="footer-projeto">
        <h2 id="footer-projeto">Conheça o ciclo</h2>
        <ul>
          <li><a href="<?= Url::escapar('/sobre/') ?>">Sobre o EcoDrop</a></li>
          <li><a href="<?= Url::escapar('/') ?>#como-funciona">Como funciona</a></li>
          <li><a href="<?= Url::escapar('/') ?>#materiais">Materiais recicláveis</a></li>
        </ul>
      </nav>

      <nav class="eco-footer__nav" aria-labelledby="footer-participe">
        <h2 id="footer-participe">Faça parte</h2>
        <ul>
          <li><a href="<?= Url::escapar('/app/cadastro/') ?>">Criar minha conta</a></li>
          <li><a href="<?= Url::escapar('/app/login/') ?>">Entrar no EcoDrop</a></li>
          <li><a href="<?= Url::escapar('/app/inicio/') ?>">Meu painel</a></li>
          <li><a href="<?= Url::escapar('/perguntas-frequentes/') ?>">Perguntas frequentes</a></li>
          <li><a href="<?= Url::escapar('/contato/') ?>">Contato</a></li>
        </ul>
      </nav>

      <div class="eco-footer__purpose">
        <h2>Nada termina.<br> Tudo se transforma.</h2>
        <a class="eco-footer__cta" href="<?= Url::escapar('/app/cadastro/') ?>">Começar meu ciclo <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>
      </div>
    </div>

    <div class="eco-footer__wordmark" data-footer-wordmark aria-hidden="true">
      <svg viewBox="0 0 1120 300" focusable="false">
        <defs>
          <radialGradient id="eco-footer-reveal" gradientUnits="userSpaceOnUse" cx="560" cy="150" r="260">
            <stop offset="0%" stop-color="white"/>
            <stop offset="35%" stop-color="white" stop-opacity=".85"/>
            <stop offset="100%" stop-color="black"/>
          </radialGradient>
          <mask id="eco-footer-mask" maskUnits="userSpaceOnUse" x="0" y="0" width="1120" height="300">
            <rect width="1120" height="300" fill="url(#eco-footer-reveal)"/>
          </mask>
        </defs>
        <image class="eco-footer__nome-base" href="<?= Url::escapar('/frontend/assets/img/logos/nome.png') ?>" x="0" y="0" width="1120" height="300" preserveAspectRatio="xMidYMid meet"/>
        <image class="eco-footer__reveal" href="<?= Url::escapar('/frontend/assets/img/logos/nome.png') ?>" x="0" y="0" width="1120" height="300" preserveAspectRatio="xMidYMid meet" mask="url(#eco-footer-mask)"/>
      </svg>
    </div>

    <div class="eco-footer__bottom">
      <p>&copy; <?= date('Y') ?> EcoDrop. Um novo ciclo começa com você.</p>
      <nav class="eco-footer__legal" aria-label="Informações legais">
        <a href="<?= Url::escapar('/termos-de-uso/') ?>">Termos de uso</a>
        <a href="<?= Url::escapar('/politica-de-privacidade/') ?>">Política de privacidade</a>
      </nav>
      <a href="#conteudo">Voltar ao topo <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20V4m-6 6 6-6 6 6"/></svg></a>
    </div>
  </div>
</footer>
