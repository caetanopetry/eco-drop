/** Troca os formulários sem recarregar a página e mantém as URLs públicas. */
(() => {
    const tela = document.querySelector('[data-auth-troca]');
    if (!tela) return;

    const paineis = [...tela.querySelectorAll('[data-auth-painel]')];
    const links = [...tela.querySelectorAll('[data-auth-modo]')];
    const caminhos = Object.fromEntries(links.map(link => [link.dataset.authModo, new URL(link.href).pathname]));

    function atualizarPaineis(modo) {
        paineis.forEach(painel => {
            const ativo = painel.dataset.authPainel === modo;
            painel.inert = !ativo;
            painel.setAttribute('aria-hidden', String(!ativo));
        });
    }

    // Sem JS, apenas o formulário da rota fica visível. Com JS, ambos
    // permanecem renderizados para animar a saída e a entrada sem cortes.
    tela.classList.add('auth-interativa');
    paineis.forEach(painel => { painel.hidden = false; });
    atualizarPaineis(tela.dataset.modo);
    void tela.offsetWidth;
    tela.classList.add('auth-pronta');

    function trocar(modo, focar = true) {
        if (tela.dataset.modo === modo) return;
        tela.dataset.modo = modo;
        atualizarPaineis(modo);
        document.title = `${modo === 'cadastro' ? 'Criar conta' : 'Entrar'} — EcoDrop`;
        if (focar) {
            tela.querySelector(`[data-auth-painel="${modo}"] h1`).focus({ preventScroll: true });
            window.scrollTo({ top: 0, behavior: 'instant' });
        }
    }

    links.forEach(link => link.addEventListener('click', evento => {
        if (evento.defaultPrevented || evento.button !== 0 || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey) return;
        evento.preventDefault();
        const modo = link.dataset.authModo;
        if (tela.dataset.modo === modo) return;
        history.pushState(null, '', link.href);
        trocar(modo);
    }));

    window.addEventListener('popstate', () => {
        const modo = Object.keys(caminhos).find(chave => caminhos[chave] === window.location.pathname);
        if (modo) trocar(modo);
    });
})();
