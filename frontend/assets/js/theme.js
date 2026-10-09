/* Executado no head, antes dos estilos e da primeira renderização. */
(() => {
    'use strict';
    const root = document.documentElement;
    const chave = 'ecodrop-theme';
    const sistema = window.matchMedia('(prefers-color-scheme: dark)');
    let escolha = null;
    try {
        const salva = localStorage.getItem(chave);
        if (salva === 'light' || salva === 'dark') escolha = salva;
    } catch (_) { /* O tema continua funcionando quando o armazenamento é bloqueado. */ }

    function aplicar(tema) {
        root.dataset.theme = tema;
        document.querySelectorAll('[data-theme-toggle]').forEach(botao => {
            const escuro = tema === 'dark';
            botao.setAttribute('aria-label', escuro ? 'Ativar tema claro' : 'Ativar tema escuro');
            botao.setAttribute('title', escuro ? 'Ativar tema claro' : 'Ativar tema escuro');
            botao.setAttribute('aria-pressed', String(escuro));
        });
    }
    aplicar(escolha || (sistema.matches ? 'dark' : 'light'));

    document.addEventListener('DOMContentLoaded', () => {
        aplicar(root.dataset.theme);
        document.querySelectorAll('[data-theme-toggle]').forEach(botao => {
            botao.hidden = false;
            botao.addEventListener('click', () => {
                escolha = root.dataset.theme === 'dark' ? 'light' : 'dark';
                aplicar(escolha);
                try { localStorage.setItem(chave, escolha); } catch (_) { /* Sem persistência. */ }
            });
        });
    });
    sistema.addEventListener('change', () => {
        if (escolha === null) aplicar(sistema.matches ? 'dark' : 'light');
    });
    window.addEventListener('storage', evento => {
        if (evento.key !== chave && evento.key !== null) return;
        escolha = evento.newValue === 'light' || evento.newValue === 'dark' ? evento.newValue : null;
        aplicar(escolha || (sistema.matches ? 'dark' : 'light'));
    });
})();
