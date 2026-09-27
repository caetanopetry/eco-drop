(() => {
    const movimentoReduzido = window.matchMedia('(prefers-reduced-motion: reduce)');
    if ('IntersectionObserver' in window && !movimentoReduzido.matches) {
        const observador = new IntersectionObserver((entradas) => {
            entradas.forEach((entrada) => {
                if (!entrada.isIntersecting) return;
                entrada.target.classList.add('revelado');
                observador.unobserve(entrada.target);
            });
        }, { threshold: 0.12 });
        document.querySelectorAll('[data-revelar]').forEach((elemento) => observador.observe(elemento));
    }
})();
