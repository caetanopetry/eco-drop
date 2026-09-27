<div class="faq-busca" data-faq-busca hidden>
  <label for="faq-pesquisa">Qual é a sua dúvida?</label>
  <div class="faq-busca-campo"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg><input id="faq-pesquisa" type="search" placeholder="Busque por cadastro, materiais, pontos…" autocomplete="off" aria-controls="faq-resultados"><button type="button" data-faq-limpar hidden>Limpar</button></div>
  <p class="faq-contagem" role="status" aria-live="polite" aria-atomic="true" data-faq-contagem></p>
</div>
<div class="institucional-documento" id="faq-resultados">
  <nav class="institucional-indice" aria-label="Assuntos das perguntas"><h2>Encontre sua dúvida</h2><a href="#primeiros-passos">Primeiros passos</a><a href="#materiais-pontos">Materiais e pontos</a><a href="#conta-privacidade">Conta e privacidade</a></nav>
  <div class="institucional-texto">
    <section id="primeiros-passos" class="faq-grupo"><h2>Primeiros passos</h2>
      <details class="faq-item" open><summary>O que é o EcoDrop?</summary><div><p>É uma plataforma de coleta seletiva incentivada: a proposta é associar descartes validados a uma conta, gerar pontos e permitir o resgate de recompensas. A versão atual demonstra esse ciclo com simulações.</p></div></details>
      <details class="faq-item"><summary>Como posso participar?</summary><div><p><a href="<?= Url::escapar('/app/cadastro/') ?>">Crie sua conta</a> com nome, e-mail, CPF e senha. Depois de entrar, você pode conhecer o painel e acompanhar as funcionalidades disponíveis na demonstração.</p></div></details>
      <details class="faq-item"><summary>Já existem estações físicas em funcionamento?</summary><div><p>Esta versão utiliza estações demonstrativas. Os nomes e endereços exibidos no ambiente de teste não representam pontos de coleta confirmados. A implantação de estações físicas faz parte das próximas etapas.</p></div></details>
      <details class="faq-item"><summary>Como funciona a identificação na estação?</summary><div><p>A proposta usa uma sessão temporária com QR Code para conectar a conta à estação. Na demonstração, siga as instruções exibidas na interface da estação. O descarte é registrado pelo fluxo controlado da estação.</p></div></details>
    </section>
    <section id="materiais-pontos" class="faq-grupo"><h2>Materiais e pontos</h2>
      <details class="faq-item"><summary>Quais materiais fazem parte do projeto?</summary><div><p>Plástico, papel, vidro e óleo de cozinha usado estão previstos nesta primeira versão. Confira a seção de <a href="<?= Url::escapar('/') ?>#materiais">materiais recicláveis</a> para conhecer cada categoria.</p></div></details>
      <details class="faq-item"><summary>Como os pontos são calculados?</summary><div><p>O cálculo considera o material, a quantidade validada e a regra de pontuação configurada. As movimentações podem ser consultadas no <a href="<?= Url::escapar('/app/extrato/') ?>">extrato da sua conta</a>. Os valores da demonstração podem mudar durante os testes.</p></div></details>
      <details class="faq-item"><summary>Posso trocar pontos por dinheiro ou produtos reais?</summary><div><p>Na versão demonstrativa, pontos, recompensas e vouchers são fictícios. A área de saques demonstra trocas por recompensas, sem transferência de dinheiro ou garantia de retirada de produtos em estabelecimentos reais.</p></div></details>
    </section>
    <section id="conta-privacidade" class="faq-grupo"><h2>Conta e privacidade</h2>
      <details class="faq-item"><summary>Por que o cadastro pede CPF?</summary><div><p>O CPF é usado para identificar o cadastro e evitar contas duplicadas. O sistema também verifica se o e-mail já está associado a outra conta. Veja mais na <a href="<?= Url::escapar('/politica-de-privacidade/') ?>">Política de privacidade</a>.</p></div></details>
      <details class="faq-item"><summary>Esqueci minha senha. O que faço?</summary><div><p>A recuperação automática de senha ainda não está disponível. Consulte a <a href="<?= Url::escapar('/contato/') ?>">página de contato</a> para verificar a disponibilidade do atendimento. Durante um teste acompanhado, peça orientação à pessoa responsável pela demonstração.</p></div></details>
      <details class="faq-item"><summary>Onde encontro informações sobre meus dados?</summary><div><p>A <a href="<?= Url::escapar('/politica-de-privacidade/') ?>">Política de privacidade</a> explica os dados utilizados, os cookies de sessão e as limitações atuais dos recursos de atendimento.</p></div></details>
    </section>
    <aside class="institucional-nota"><h2>Ainda ficou uma dúvida?</h2><p>Veja as orientações para falar sobre o projeto, sugerir melhorias ou relatar um problema.</p><a class="botao botao-secundario" href="<?= Url::escapar('/contato/') ?>">Ir para contato</a></aside>
  </div>
</div>
