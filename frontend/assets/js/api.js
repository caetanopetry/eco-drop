/**
 * EcoDrop — cliente HTTP simples para consumir a API JSON do backend.
 * Nunca decide regra de negócio aqui: apenas envia e recebe dados.
 */
const EcoDropApi = (() => {
    const caminhoDoScript = document.currentScript?.src
        ? new URL(document.currentScript.src).pathname
        : '';

    function baseDaAplicacao() {
        if (window.EcoDropBaseUrl) {
            return window.EcoDropBaseUrl;
        }

        // O próprio caminho do arquivo é estável em todas as páginas e evita
        // depender de um script inline para descobrir a subpasta da aplicação.
        const marcador = '/frontend/assets/js/';
        const posicao = caminhoDoScript.indexOf(marcador);
        return posicao >= 0 ? (caminhoDoScript.slice(0, posicao) || '/') : '/';
    }

    function caminhoComBase(caminho) {
        if (/^https?:\/\//i.test(caminho)) {
            return caminho;
        }

        const base = baseDaAplicacao().replace(/\/$/, '');
        return caminho.startsWith('/') ? `${base}${caminho}` : caminho;
    }

    async function post(caminho, corpo) {
        const opcoes = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(corpo),
        };

        let resposta;
        try {
            resposta = await fetch(caminhoComBase(caminho), opcoes);
        } catch {
            return {
                sucesso: false,
                erro: { mensagem: 'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.' },
            };
        }

        let json;
        try {
            json = await resposta.json();
        } catch {
            return {
                sucesso: false,
                erro: { mensagem: 'Resposta inesperada do servidor.' },
            };
        }

        return json;
    }

    return { post };
})();
