/**
 * EcoDrop — cliente HTTP simples para consumir a API JSON do backend.
 * Nunca decide regra de negócio aqui: apenas envia e recebe dados.
 */
const EcoDropApi = (() => {
    function caminhoComBase(caminho) {
        if (/^https?:\/\//i.test(caminho)) {
            return caminho;
        }

        const base = (window.EcoDropBaseUrl || '/').replace(/\/$/, '');
        return caminho.startsWith('/') ? `${base}${caminho}` : caminho;
    }

    async function chamar(caminho, { metodo = 'GET', corpo = null } = {}) {
        const opcoes = {
            method: metodo,
            headers: {},
            credentials: 'same-origin',
        };

        if (corpo !== null) {
            opcoes.headers['Content-Type'] = 'application/json';
            opcoes.body = JSON.stringify(corpo);
        }

        let resposta;
        try {
            resposta = await fetch(caminhoComBase(caminho), opcoes);
        } catch (erroRede) {
            return {
                sucesso: false,
                erro: { mensagem: 'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.' },
            };
        }

        let json;
        try {
            json = await resposta.json();
        } catch (erroParse) {
            return {
                sucesso: false,
                erro: { mensagem: 'Resposta inesperada do servidor.' },
            };
        }

        return json;
    }

    return {
        post: (caminho, corpo) => chamar(caminho, { metodo: 'POST', corpo }),
        get: (caminho) => chamar(caminho, { metodo: 'GET' }),
    };
})();
