/**
 * EcoDrop — envio dos formulários de autenticação (login e cadastro) via API,
 * com feedback visual em componentes de alerta (nunca alert() genérico — seção 24).
 */
document.addEventListener('DOMContentLoaded', () => {
    const formularioLogin = document.querySelector('[data-formulario="login"]');
    const formularioCadastro = document.querySelector('[data-formulario="cadastro"]');

    if (formularioLogin) {
        configurarFormulario(formularioLogin, '/backend/api/auth/login.php');
    }

    if (formularioCadastro) {
        configurarFormulario(formularioCadastro, '/backend/api/auth/register.php');
    }
});

function configurarFormulario(formulario, endpoint) {
    const areaFeedback = formulario.querySelector('[data-area-feedback]');
    const botaoEnviar = formulario.querySelector('[type="submit"]');

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        limparErros(formulario);
        limparFeedback(areaFeedback);

        const dados = Object.fromEntries(new FormData(formulario).entries());

        definirCarregando(botaoEnviar, true);
        const resposta = await EcoDropApi.post(endpoint, dados);
        definirCarregando(botaoEnviar, false);

        if (!resposta.sucesso) {
            mostrarAlerta(areaFeedback, 'erro', resposta.erro?.mensagem || 'Não foi possível concluir a operação.');
            if (resposta.erro?.campos) {
                mostrarErrosDeCampo(formulario, resposta.erro.campos);
            }
            return;
        }

        mostrarAlerta(areaFeedback, 'sucesso', 'Tudo certo! Redirecionando…');
        window.location.href = resposta.dados.redirecionar_para || window.EcoDropBaseUrl || '/';
    });
}

function definirCarregando(botao, carregando) {
    if (!botao) return;
    botao.disabled = carregando;
    botao.dataset.textoOriginal = botao.dataset.textoOriginal || botao.textContent;
    botao.textContent = carregando ? 'Enviando…' : botao.dataset.textoOriginal;
}

function mostrarAlerta(area, tipo, mensagem) {
    if (!area) return;
    const div = document.createElement('div');
    div.className = `alerta alerta-${tipo}`;
    div.setAttribute('role', tipo === 'erro' ? 'alert' : 'status');
    div.textContent = mensagem;
    area.appendChild(div);
}

function limparFeedback(area) {
    if (area) area.innerHTML = '';
}

function mostrarErrosDeCampo(formulario, campos) {
    Object.entries(campos).forEach(([nomeCampo, mensagem]) => {
        const campoWrapper = formulario.querySelector(`[data-campo="${nomeCampo}"]`);
        if (!campoWrapper) return;
        campoWrapper.classList.add('com-erro');
        const spanErro = campoWrapper.querySelector('[data-erro-campo]');
        if (spanErro) spanErro.textContent = mensagem;
    });
}

function limparErros(formulario) {
    formulario.querySelectorAll('.campo.com-erro').forEach((campo) => {
        campo.classList.remove('com-erro');
        const spanErro = campo.querySelector('[data-erro-campo]');
        if (spanErro) spanErro.textContent = '';
    });
}
