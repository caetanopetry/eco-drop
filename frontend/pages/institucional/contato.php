<?php
$whatsapp = preg_replace('/\D/', '', (string) Config::get('contato.whatsapp', ''));
$whatsapp = preg_match('/^[1-9][0-9]{9,14}$/', $whatsapp) ? $whatsapp : '';
$whatsappExibicao = '+' . $whatsapp;
if (preg_match('/^55([1-9][0-9])([0-9]{5})([0-9]{4})$/', $whatsapp, $partes)) {
    $whatsappExibicao = '+55 ' . $partes[1] . ' ' . $partes[2] . '-' . $partes[3];
}
$instagram = ltrim(trim((string) Config::get('contato.instagram', '')), '@');
$instagram = preg_match('/^[a-zA-Z0-9._]{1,30}$/', $instagram) ? $instagram : '';
$canais = [
    ['nome' => 'E-mail', 'detalhe' => $emailContato, 'url' => $emailContato !== '' ? 'mailto:' . $emailContato : '', 'acao' => 'Escrever e-mail', 'icone' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/>'],
    ['nome' => 'WhatsApp', 'detalhe' => $whatsapp !== '' ? $whatsappExibicao : '', 'url' => $whatsapp !== '' ? 'https://wa.me/' . $whatsapp : '', 'acao' => 'Abrir conversa', 'icone' => '<path d="M20.5 11.5a8.5 8.5 0 0 1-12.7 7.4L3 20l1.2-4.6A8.5 8.5 0 1 1 20.5 11.5Z"/><path d="m8 7 2 3-1.2 1.2a7 7 0 0 0 4 4L14 14l3 2c-1 3-4 2-7-1s-4-6-2-8Z"/>'],
    ['nome' => 'Instagram', 'detalhe' => $instagram !== '' ? '@' . $instagram : '', 'url' => $instagram !== '' ? 'https://www.instagram.com/' . $instagram . '/' : '', 'acao' => 'Visitar perfil', 'icone' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>'],
];
?>
<div class="contato-grade">
  <aside class="contato-canais" aria-label="Canais de contato">
    <?php foreach ($canais as $canal): ?>
      <?php if ($canal['url'] !== ''): ?>
        <a class="contato-cartao" href="<?= $escapar($canal['url']) ?>">
      <?php else: ?>
        <article class="contato-cartao">
      <?php endif; ?>
        <div class="contato-cartao-topo">
          <span class="contato-icone"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?= $canal['icone'] ?></svg></span>
          <?php if ($canal['url'] !== ''): ?>
            <svg class="contato-seta" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
          <?php else: ?>
            <span class="contato-em-breve">Indisponível</span>
          <?php endif; ?>
        </div>
        <h2><?= $escapar($canal['nome']) ?></h2>
        <?php if ($canal['url'] !== ''): ?>
          <p><?= $escapar($canal['detalhe']) ?></p><span class="contato-cartao-acao"><?= $escapar($canal['acao']) ?></span>
        <?php else: ?>
          <p>Este canal ainda não foi configurado.</p>
        <?php endif; ?>
      <?php if ($canal['url'] !== ''): ?></a><?php else: ?></article><?php endif; ?>
    <?php endforeach; ?>
    <a class="contato-duvidas" href="<?= Url::escapar('/perguntas-frequentes/') ?>">Procurando uma resposta rápida?<span>Veja as perguntas frequentes <span aria-hidden="true">→</span></span></a>
  </aside>

  <section class="contato-painel" aria-labelledby="contato-mensagem-titulo">
    <h2 id="contato-mensagem-titulo">Vamos conversar?</h2>
    <p class="contato-introducao">Uma dúvida, uma ideia ou uma nova parceria. A gente quer ouvir você.</p>
    <?php if ($emailContato === ''): ?>
      <p class="contato-disponibilidade" id="contato-disponibilidade"><span aria-hidden="true"></span>O endereço de contato não está configurado.</p>
    <?php endif; ?>
    <form class="contato-formulario"<?= $emailContato !== '' ? ' action="mailto:' . $escapar($emailContato) . '" method="get" data-contato-formulario' : ' aria-describedby="contato-disponibilidade"' ?>>
      <fieldset<?= $emailContato === '' ? ' disabled' : '' ?>>
        <legend class="somente-leitor-tela">Escreva sua mensagem</legend>
        <div class="contato-campos-duplos">
          <div class="campo"><label for="contato-nome">Seu nome</label><input id="contato-nome" name="nome" autocomplete="name" placeholder="Como podemos chamar você?" maxlength="120" required></div>
          <div class="campo"><label for="contato-email">Seu e-mail</label><input id="contato-email" name="email" type="email" autocomplete="email" placeholder="voce@exemplo.com" maxlength="254" required></div>
        </div>
        <div class="campo"><label for="contato-assunto">Sobre o que vamos conversar?</label><select id="contato-assunto" name="subject" required><option value="" disabled selected>Selecione um assunto</option><option value="Dúvida sobre o EcoDrop">Tenho uma dúvida</option><option value="Sugestão para o EcoDrop">Quero compartilhar uma ideia</option><option value="Parceria com o EcoDrop">Quero conversar sobre uma parceria</option><option value="Privacidade e minha conta">Privacidade e minha conta</option><option value="Outro assunto">Outro assunto</option></select></div>
        <div class="campo"><label for="contato-mensagem">Sua mensagem</label><textarea id="contato-mensagem" name="body" rows="4" placeholder="Conte um pouco mais. Estamos aqui para ouvir." maxlength="2000" required></textarea></div>
        <button class="botao botao-primario contato-enviar" type="submit"><?= $emailContato !== '' ? 'Abrir no meu e-mail' : 'Indisponível' ?><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m21 3-7 18-4-7-7-4 18-7ZM10 14 21 3"/></svg></button>
      </fieldset>
      <?php if ($emailContato !== ''): ?><p class="contato-formulario-nota">A mensagem será preparada no seu aplicativo de e-mail. Você revisa e envia por lá.</p><?php endif; ?>
      <p class="contato-privacidade">Seus dados merecem cuidado. Consulte nossa <a href="<?= Url::escapar('/politica-de-privacidade/') ?>">Política de privacidade</a>.</p>
    </form>
  </section>
</div>
<?php if ($emailContato !== ''): ?><script src="<?= Url::escapar('/frontend/assets/js/contato.js') ?>" defer></script><?php endif; ?>
