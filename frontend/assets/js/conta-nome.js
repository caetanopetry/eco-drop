(() => {
    const editor = document.querySelector('.conta-nome-editor');
    if (!editor) return;
    const form = editor.querySelector('form');
    const nome = form.querySelector('#nome');
    const salvar = form.querySelector('button[type="submit"]');
    const cancelar = form.querySelector('[data-cancelar-nome]');
    const feedback = form.querySelector('#nome-feedback');
    const normalizar = valor => valor.replace(/[\s\p{Z}]+/gu, ' ').trim();
    let enviando = false;

    const validar = () => {
        const valor = normalizar(nome.value);
        const mensagem = !valor ? 'Informe seu nome.'
            : Array.from(valor).length > 150 ? 'Use até 150 caracteres.' : '';
        nome.setCustomValidity(mensagem);
        return { valor, mensagem };
    };
    const restaurar = () => {
        nome.value = nome.dataset.nomeAtual;
        nome.setCustomValidity('');
        feedback.textContent = '';
    };

    nome.addEventListener('input', () => {
        feedback.textContent = validar().mensagem;
    });
    editor.addEventListener('toggle', () => {
        if (editor.open) nome.focus();
        else if (!enviando) restaurar();
    });
    cancelar.addEventListener('click', evento => {
        evento.preventDefault();
        if (enviando) return;
        restaurar();
        editor.open = false;
        editor.querySelector('summary').focus();
    });
    form.addEventListener('submit', evento => {
        if (enviando) {
            evento.preventDefault();
            return;
        }
        const { valor, mensagem } = validar();
        if (mensagem) {
            evento.preventDefault();
            feedback.textContent = mensagem;
            nome.reportValidity();
            return;
        }
        if (valor === nome.dataset.nomeAtual) {
            evento.preventDefault();
            feedback.textContent = 'Seu nome já está atualizado.';
            return;
        }
        nome.value = valor;
        enviando = true;
        salvar.disabled = true;
        cancelar.setAttribute('aria-disabled', 'true');
        feedback.textContent = 'Salvando nome…';
    });
    window.addEventListener('pageshow', () => {
        enviando = false;
        salvar.disabled = false;
        cancelar.removeAttribute('aria-disabled');
    });
    if (editor.open) nome.focus();
})();
