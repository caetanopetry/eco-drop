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
