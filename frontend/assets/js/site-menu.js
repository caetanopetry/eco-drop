(() => {
    const botao = document.querySelector('.site-menu-botao');
    const menu = document.getElementById('site-menu');
    if (!botao || !menu) return;

    const mobile = window.matchMedia('(max-width: 940px)');
    const definirAberto = (aberto) => {
        botao.setAttribute('aria-expanded', String(aberto));
        menu.hidden = mobile.matches && !aberto;
    };
    const atualizarLayout = () => {
        const foco = document.activeElement;
        botao.hidden = !mobile.matches;
        definirAberto(false);
        if (mobile.matches && menu.contains(foco)) botao.focus();
        if (!mobile.matches && foco === botao) menu.querySelector('a').focus();
    };

    botao.addEventListener('click', () => {
        definirAberto(botao.getAttribute('aria-expanded') !== 'true');
    });
    menu.addEventListener('click', (evento) => {
        if (mobile.matches && evento.target.closest('a')) definirAberto(false);
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && mobile.matches && botao.getAttribute('aria-expanded') === 'true') {
            definirAberto(false);
            botao.focus();
        }
    });
    mobile.addEventListener('change', atualizarLayout);
    atualizarLayout();
})();
