(() => {
    const formulario = document.querySelector('[data-contato-formulario]');
    if (!formulario) return;
    formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();
        if (!formulario.reportValidity()) return;
        const dados = new FormData(formulario);
        const nome = String(dados.get('nome')).trim();
        const email = String(dados.get('email')).trim();
        const mensagem = String(dados.get('body')).trim();
        const corpo = `Nome: ${nome}\nE-mail para retorno: ${email}\n\n${mensagem}`;
        const assunto = `EcoDrop — ${dados.get('subject')}`;
        window.location.href = `${formulario.action}?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
    });
})();
