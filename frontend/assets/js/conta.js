document.querySelectorAll('.conta-avatar img, .perfil-avatar img').forEach((foto) => {
    const ocultarFoto = () => { foto.hidden = true; };
    foto.addEventListener('error', ocultarFoto);
    if (foto.complete && foto.naturalWidth === 0) ocultarFoto();
});

(() => {
    const editor = document.querySelector('.conta-foto-editor');
    if (!editor) return;

    const form = editor.querySelector('form');
    const arquivo = form.querySelector('input[type="file"]');
    const previa = form.querySelector('.conta-foto-previa');
    const imagem = previa.querySelector('img');
    const feedback = form.querySelector('#foto-feedback');
    const salvar = form.querySelector('button[type="submit"]');
    const cancelar = form.querySelector('[data-cancelar-foto]');
    let url = null;
    let selecao = 0;

    const limpar = () => {
        selecao++;
        imagem.removeAttribute('src');
        if (url) URL.revokeObjectURL(url);
        url = null;
        previa.hidden = true;
        feedback.textContent = '';
        arquivo.setCustomValidity('');
        salvar.disabled = true;
    };
    const erro = (mensagem) => {
        arquivo.setCustomValidity(mensagem);
        feedback.textContent = mensagem;
        salvar.disabled = true;
    };

    cancelar.hidden = false;
    salvar.disabled = true;
    cancelar.addEventListener('click', () => {
        form.reset();
        limpar();
        editor.open = false;
        editor.querySelector('summary').focus();
    });
    editor.addEventListener('toggle', () => {
        if (!editor.open) {
            form.reset();
            limpar();
        }
    });
    arquivo.addEventListener('change', () => {
        limpar();
        const foto = arquivo.files[0];
        if (!foto) return;
        if (foto.size > 2 * 1024 * 1024) {
            erro('A foto deve ter até 2 MB.');
            return;
        }
        if (foto.type && !['image/jpeg', 'image/png', 'image/webp'].includes(foto.type)) {
            erro('Escolha uma imagem JPG, PNG ou WebP.');
            return;
        }
        const atual = selecao;
        const leitura = new Image();
        feedback.textContent = 'Verificando a imagem…';
        leitura.onload = () => {
            if (atual !== selecao) return;
            if (leitura.naturalWidth > 4096 || leitura.naturalHeight > 4096) {
                erro('A imagem deve ter no máximo 4096 × 4096 pixels.');
                return;
            }
            imagem.src = url;
            previa.hidden = false;
            feedback.textContent = 'Foto pronta para salvar.';
            salvar.disabled = false;
        };
        leitura.onerror = () => {
            if (atual === selecao) erro('Não foi possível abrir a imagem. Escolha outro arquivo.');
        };
        url = URL.createObjectURL(foto);
        leitura.src = url;
    });
    form.addEventListener('submit', (evento) => {
        if (salvar.disabled) {
            evento.preventDefault();
            return;
        }
        salvar.disabled = true;
        cancelar.disabled = true;
        feedback.textContent = 'Salvando foto…';
    });
    window.addEventListener('pageshow', () => {
        cancelar.disabled = false;
        salvar.disabled = !arquivo.files.length || !arquivo.validity.valid || previa.hidden;
    });
})();
