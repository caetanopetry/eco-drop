<div class="faq-busca" role="search" aria-label="Buscar nas perguntas frequentes" data-faq-busca hidden>
  <label for="faq-pesquisa">Qual é a sua dúvida?</label>
  <p class="faq-busca-ajuda" id="faq-busca-ajuda">Pesquise nas perguntas e respostas. Os termos encontrados ficam sublinhados.</p>
  <div class="faq-busca-campo"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg><input id="faq-pesquisa" type="search" placeholder="Ex.: conta, pontos ou óleo de cozinha" autocomplete="off" maxlength="160" aria-describedby="faq-busca-ajuda" aria-controls="faq-resultados"><button type="button" data-faq-limpar aria-label="Limpar pesquisa" hidden>Limpar</button></div>
  <div class="faq-sugestoes" aria-label="Sugestões de pesquisa"><span>Buscas rápidas:</span><button type="button" data-faq-termo="conta">Conta</button><button type="button" data-faq-termo="pontos">Pontos</button><button type="button" data-faq-termo="materiais">Materiais</button><button type="button" data-faq-termo="senha">Senha</button></div>
</div>
<div class="institucional-documento" id="faq-resultados">
  <nav class="institucional-indice" aria-label="Assuntos das perguntas"><h2>Encontre sua dúvida</h2><a href="#primeiros-passos">Primeiros passos</a><a href="#materiais-pontos">Materiais e pontos</a><a href="#conta-privacidade">Conta e privacidade</a></nav>
  <div class="institucional-texto">
    <div class="faq-ferramentas" data-faq-ferramentas hidden><p class="faq-contagem" role="status" aria-live="polite" aria-atomic="true" data-faq-contagem></p><button type="button" class="faq-alternar" data-faq-alternar aria-controls="faq-resultados">Expandir respostas</button></div>
    <div class="faq-vazio" data-faq-vazio hidden><h2>Nenhuma resposta encontrada.</h2><p data-faq-vazio-texto></p><p>Tente uma palavra mais curta, como “conta” ou “pontos”. Você também pode <a href="<?= Url::escapar('/contato/') ?>">falar com a gente</a>.</p><button class="botao botao-secundario" type="button" data-faq-restaurar>Ver todas as perguntas</button></div>
    <section id="primeiros-passos" class="faq-grupo"><h2>Primeiros passos</h2>
      <details class="faq-item" open><summary><span>O que é o EcoDrop?</span></summary><div><p>É uma plataforma de coleta seletiva incentivada: a proposta é associar descartes validados a uma conta, gerar pontos e permitir o resgate de recompensas. A versão atual apresenta esse fluxo por meio de simulações.</p></div></details>
      <details class="faq-item"><summary><span>Como posso participar?</span></summary><div><p><a href="<?= Url::escapar('/app/cadastro/') ?>">Crie sua conta</a> com nome, e-mail, CPF e senha para acessar o painel e testar as simulações de descarte, pontos e resgates.</p></div></details>
      <details class="faq-item"><summary><span>Já existem estações físicas em funcionamento?</span></summary><div><p>Não. Os nomes e endereços de estações exibidos nesta versão são de teste e não identificam locais confirmados de coleta. Não há operação física em funcionamento.</p></div></details>
      <details class="faq-item"><summary><span>Como funciona a identificação na estação?</span></summary><div><p>A proposta usa uma sessão temporária com QR Code para conectar a conta à estação. No ambiente de demonstração, siga as instruções exibidas na interface da estação. O descarte é registrado pelo fluxo controlado da estação.</p></div></details>
    </section>
    <section id="materiais-pontos" class="faq-grupo"><h2>Materiais e pontos</h2>
      <details class="faq-item"><summary><span>Quais materiais fazem parte do projeto?</span></summary><div><p>Plástico, papel, vidro e óleo de cozinha usado estão previstos nesta primeira versão. Confira a seção de <a href="<?= Url::escapar('/') ?>#materiais">materiais recicláveis</a> para conhecer cada categoria.</p></div></details>
      <details class="faq-item"><summary><span>Como os pontos são calculados?</span></summary><div><p>O cálculo considera o material, a quantidade validada e a regra de pontuação configurada. As movimentações podem ser consultadas no <a href="<?= Url::escapar('/app/extrato/') ?>">extrato da sua conta</a>. Os parâmetros podem ser atualizados durante os testes.</p></div></details>
      <details class="faq-item"><summary><span>Posso trocar pontos por dinheiro ou produtos reais?</span></summary><div><p>Não. No ambiente de demonstração, pontos, recompensas e vouchers servem apenas para testar o fluxo: não têm valor monetário, não geram pagamentos e não garantem produtos ou atendimento em estabelecimentos reais.</p></div></details>
    </section>
    <section id="conta-privacidade" class="faq-grupo"><h2>Conta e privacidade</h2>
      <details class="faq-item"><summary><span>Por que o cadastro pede CPF?</span></summary><div><p>O CPF é usado para identificar o cadastro e evitar contas duplicadas. O sistema também verifica se o e-mail já está associado a outra conta. Veja mais na <a href="<?= Url::escapar('/politica-de-privacidade/') ?>">Política de privacidade</a>.</p></div></details>
      <details class="faq-item"><summary><span>Esqueci minha senha. O que faço?</span></summary><div><p>Use a opção <a href="<?= Url::escapar('/app/esqueci-senha/') ?>">esqueci minha senha</a> na tela de acesso. Se o e-mail estiver cadastrado, enviaremos um link para você criar uma nova senha. O link expira em 1 hora e só pode ser usado uma vez.</p></div></details>
      <details class="faq-item"><summary><span>Onde encontro informações sobre meus dados?</span></summary><div><p>A <a href="<?= Url::escapar('/politica-de-privacidade/') ?>">Política de privacidade</a> explica os dados utilizados, os cookies de sessão e as limitações atuais dos recursos de atendimento.</p></div></details>
    </section>
    <aside class="institucional-nota"><h2>Ainda ficou uma dúvida?</h2><p>Veja as orientações para falar sobre o projeto, sugerir melhorias ou relatar um problema.</p><a class="botao botao-secundario" href="<?= Url::escapar('/contato/') ?>">Ir para contato</a></aside>
  </div>
</div>
<script src="<?= Url::escapar('/frontend/assets/js/faq.js') ?>" defer></script>
