(function () {
    'use strict';

    const estacao = document.getElementById('descartes-estacao');
    const estacaoCodigo = document.getElementById('descartes-estacao-codigo');
    const estacaoLocal = document.getElementById('descartes-estacao-local');
    const material = document.getElementById('descartes-material');
    const quantidade = document.getElementById('descartes-quantidade');
    const unidade = document.getElementById('descartes-unidade');
    const minimo = document.getElementById('descartes-minimo');
    const previa = document.getElementById('descartes-previa');

    const numero = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    const decimal = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 3,
        maximumFractionDigits: 3
    });

    function atualizarEstacao() {
        if (!estacao || !estacaoCodigo || !estacaoLocal) {
            return;
        }

        const opcao = estacao.options[estacao.selectedIndex];
        if (!opcao || !opcao.value) {
            estacaoCodigo.textContent = '—';
            estacaoLocal.textContent = 'Selecione uma estação para ver o código.';
            return;
        }

        estacaoCodigo.textContent = opcao.dataset.codigo || '—';

        const nome = opcao.dataset.nome || '';
        const endereco = opcao.dataset.endereco || '';
        estacaoLocal.textContent = [nome, endereco].filter(Boolean).join(' · ');
    }

    function atualizarMaterial() {
        if (!material || !quantidade || !unidade || !minimo || !previa) {
            return;
        }

        const opcao = material.options[material.selectedIndex];
        if (!opcao) {
            return;
        }

        const unidadeAtual = opcao.dataset.unidade || 'kg';
        const minimoAtual = Number.parseFloat(opcao.dataset.minimo || '0') || 0;
        const pontosPorUnidade = Number.parseFloat(opcao.dataset.pontos || '0') || 0;
        const valor = Number.parseFloat(String(quantidade.value).replace(',', '.')) || 0;

        unidade.textContent = unidadeAtual;
        minimo.textContent = 'Mínimo para pontuar: ' + decimal.format(minimoAtual) + ' ' + unidadeAtual;

        const valida = valor >= minimoAtual && valor > 0;
        previa.textContent = valida ? numero.format(Math.round(valor * pontosPorUnidade)) + ' pts' : '0 pts';
    }

    if (estacao) {
        estacao.addEventListener('change', atualizarEstacao);
        atualizarEstacao();
    }

    if (material) {
        material.addEventListener('change', atualizarMaterial);
    }

    if (quantidade) {
        quantidade.addEventListener('input', atualizarMaterial);
    }

    atualizarMaterial();
})();
