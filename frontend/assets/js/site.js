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

    const busca = document.querySelector('[data-faq-busca]');
    if (!busca) return;
    const campo = busca.querySelector('input');
    const limpar = busca.querySelector('[data-faq-limpar]');
    const contagem = busca.querySelector('[data-faq-contagem]');
    const grupos = [...document.querySelectorAll('.faq-grupo')];
    const itens = [...document.querySelectorAll('.faq-item')];
    const abertos = new Set(itens.filter((item) => item.open));
    const normalizar = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
    const textos = new Map(itens.map((item) => [item, normalizar(item.textContent)]));
    const filtrar = () => {
        const termos = normalizar(campo.value.trim()).split(/\s+/).filter(Boolean);
        let encontrados = 0;
        itens.forEach((item) => {
            const corresponde = termos.every((termo) => textos.get(item).includes(termo));
            item.hidden = !corresponde;
            item.open = termos.length ? corresponde : abertos.has(item);
            if (corresponde) encontrados++;
        });
        grupos.forEach((grupo) => {
            grupo.hidden = ![...grupo.querySelectorAll('.faq-item')].some((item) => !item.hidden);
        });
        limpar.hidden = !campo.value;
        contagem.textContent = termos.length
            ? (encontrados ? `${encontrados} ${encontrados === 1 ? 'pergunta encontrada' : 'perguntas encontradas'}.` : 'Nenhuma pergunta encontrada. Tente outra palavra ou fale com a gente pelo contato.')
            : 'Explore os assuntos abaixo ou pesquise uma palavra.';
    };
    campo.addEventListener('input', filtrar);
    limpar.addEventListener('click', () => { campo.value = ''; filtrar(); campo.focus(); });
    document.querySelectorAll('.institucional-indice a').forEach((link) => {
        link.addEventListener('click', () => { if (campo.value) { campo.value = ''; filtrar(); } });
    });
    busca.hidden = false;
    filtrar();
})();
