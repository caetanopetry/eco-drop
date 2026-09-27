(() => {
    const busca = document.querySelector('[data-faq-busca]');
    if (!busca) return;

    const campo = busca.querySelector('input');
    const limpar = busca.querySelector('[data-faq-limpar]');
    const resultados = document.getElementById('faq-resultados');
    const contagem = resultados.querySelector('[data-faq-contagem]');
    const alternar = resultados.querySelector('[data-faq-alternar]');
    const vazio = resultados.querySelector('[data-faq-vazio]');
    const grupos = [...resultados.querySelectorAll('.faq-grupo')];
    const itens = [...resultados.querySelectorAll('.faq-item')];
    const normalizar = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
    const textos = new Map(itens.map((item) => [item, normalizar(item.textContent)]));
    let abertosAntesDaBusca = new Set();
    let pesquisando = false;

    // Trabalha apenas com nós de texto: links e marcação nunca recebem HTML da busca.
    const destacar = (item, termos) => {
        item.querySelectorAll('mark[data-faq-destaque]').forEach((marca) => marca.replaceWith(document.createTextNode(marca.textContent)));
        item.normalize();
        if (!termos.length) return;

        const leitor = document.createTreeWalker(item, NodeFilter.SHOW_TEXT);
        const nos = [];
        while (leitor.nextNode()) nos.push(leitor.currentNode);
        nos.forEach((no) => {
            const original = no.nodeValue;
            let texto = '';
            let posicao = 0;
            const posicoes = [];
            // Guarda as posições originais para preservar acentos e caracteres Unicode.
            for (const caractere of original) {
                const simples = normalizar(caractere);
                for (let i = 0; i < simples.length; i++) posicoes.push([posicao, posicao + caractere.length]);
                if (!simples.length && posicoes.length) posicoes[posicoes.length - 1][1] += caractere.length;
                texto += simples;
                posicao += caractere.length;
            }
            const trechos = [];
            termos.forEach((termo) => {
                let inicio = texto.indexOf(termo);
                while (inicio !== -1) {
                    trechos.push([posicoes[inicio][0], posicoes[inicio + termo.length - 1][1]]);
                    inicio = texto.indexOf(termo, inicio + 1);
                }
            });
            if (!trechos.length) return;
            trechos.sort((a, b) => a[0] - b[0]);
            const unidos = [];
            trechos.forEach(([inicio, fim]) => {
                const ultimo = unidos[unidos.length - 1];
                if (ultimo && inicio <= ultimo[1]) ultimo[1] = Math.max(ultimo[1], fim);
                else unidos.push([inicio, fim]);
            });
            const fragmento = document.createDocumentFragment();
            let cursor = 0;
            unidos.forEach(([inicio, fim]) => {
                fragmento.append(document.createTextNode(original.slice(cursor, inicio)));
                const marca = document.createElement('mark');
                marca.dataset.faqDestaque = '';
                marca.textContent = original.slice(inicio, fim);
                fragmento.append(marca);
                cursor = fim;
            });
            fragmento.append(document.createTextNode(original.slice(cursor)));
            no.replaceWith(fragmento);
        });
    };

    const atualizarAlternar = () => {
        const visiveis = itens.filter((item) => !item.hidden);
        alternar.disabled = !visiveis.length;
        alternar.textContent = visiveis.length && visiveis.every((item) => item.open) ? 'Recolher respostas' : 'Expandir respostas';
    };

    const filtrar = () => {
        const consulta = campo.value.trim();
        const termos = [...new Set(normalizar(consulta).split(/\s+/).filter(Boolean))];
        const temBusca = termos.length > 0;
        if (temBusca && !pesquisando) abertosAntesDaBusca = new Set(itens.filter((item) => item.open));
        let encontrados = 0;
        itens.forEach((item) => {
            const corresponde = termos.every((termo) => textos.get(item).includes(termo));
            item.hidden = !corresponde;
            destacar(item, corresponde ? termos : []);
            if (temBusca) item.open = corresponde;
            else if (pesquisando) item.open = abertosAntesDaBusca.has(item);
            if (corresponde) encontrados++;
        });
        pesquisando = temBusca;
        grupos.forEach((grupo) => { grupo.hidden = ![...grupo.querySelectorAll('.faq-item')].some((item) => !item.hidden); });
        limpar.hidden = !campo.value;
        vazio.hidden = encontrados > 0;
        vazio.querySelector('[data-faq-vazio-texto]').textContent = encontrados ? '' : `Não encontramos perguntas para “${consulta}”.`;
        contagem.textContent = temBusca
            ? `${encontrados} ${encontrados === 1 ? 'pergunta encontrada' : 'perguntas encontradas'} para “${consulta}”.`
            : `${itens.length} perguntas para ajudar você.`;
        atualizarAlternar();
    };

    const restaurar = () => { campo.value = ''; filtrar(); campo.focus(); };
    campo.addEventListener('input', filtrar);
    campo.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && campo.value) { evento.preventDefault(); restaurar(); }
    });
    limpar.addEventListener('click', restaurar);
    resultados.querySelector('[data-faq-restaurar]').addEventListener('click', restaurar);
    busca.querySelectorAll('[data-faq-termo]').forEach((botao) => {
        botao.addEventListener('click', () => { campo.value = botao.dataset.faqTermo; filtrar(); campo.focus(); });
    });
    alternar.addEventListener('click', () => {
        const visiveis = itens.filter((item) => !item.hidden);
        const abrir = !visiveis.every((item) => item.open);
        visiveis.forEach((item) => { item.open = abrir; });
        atualizarAlternar();
    });
    itens.forEach((item) => item.addEventListener('toggle', atualizarAlternar));
    resultados.querySelectorAll('.institucional-indice a').forEach((link) => {
        link.addEventListener('click', () => { if (campo.value) { campo.value = ''; filtrar(); } });
    });
    busca.hidden = false;
    resultados.querySelector('[data-faq-ferramentas]').hidden = false;
    filtrar();
})();
