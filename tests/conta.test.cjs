const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const codigo = fs.readFileSync(path.join(__dirname, '../frontend/assets/js/conta.js'), 'utf8');

function tela() {
    const elemento = () => ({
        hidden: false, disabled: false, textContent: '', listeners: {},
        addEventListener(tipo, callback) { this.listeners[tipo] = callback; },
        removeAttribute(nome) { delete this[nome]; },
        focus() { this.focado = true; },
    });
    const editor = elemento(), form = elemento(), arquivo = elemento();
    const previa = elemento(), imagem = elemento(), feedback = elemento();
    const salvar = elemento(), cancelar = elemento(), summary = elemento();
    const leituras = [], revogadas = [];
    editor.open = true;
    previa.hidden = true;
    arquivo.files = [];
    arquivo.validity = { valid: false };
    arquivo.setCustomValidity = mensagem => {
        arquivo.validationMessage = mensagem;
        arquivo.validity.valid = !mensagem && !!arquivo.files.length;
    };
    form.reset = () => { arquivo.files = []; };
    editor.querySelector = seletor => ({ form, summary })[seletor];
    form.querySelector = seletor => ({
        'input[type="file"]': arquivo, '.conta-foto-previa': previa,
        '#foto-feedback': feedback, 'button[type="submit"]': salvar,
        '[data-cancelar-foto]': cancelar,
    })[seletor];
    previa.querySelector = () => imagem;
    vm.runInNewContext(codigo, {
        document: { querySelectorAll: () => [], querySelector: () => editor },
        window: elemento(),
        Image: function () { leituras.push(this); },
        URL: {
            createObjectURL: () => `blob:foto-${leituras.length}`,
            revokeObjectURL: url => revogadas.push(url),
        },
    });
    const selecionar = (foto = { size: 100, type: 'image/png' }) => {
        arquivo.files = foto ? [foto] : [];
        arquivo.listeners.change();
    };
    const carregar = (largura = 200, altura = 200, leitura = leituras.at(-1)) => {
        leitura.naturalWidth = largura;
        leitura.naturalHeight = altura;
        leitura.onload();
    };
    return { editor, form, arquivo, previa, imagem, feedback, salvar, cancelar, summary, leituras, revogadas, selecionar, carregar };
}

test('prévia aguarda a leitura; salvar só fica disponível para uma foto válida', () => {
    const t = tela();
    assert.equal(t.salvar.disabled, true);
    t.selecionar();
    assert.equal(t.salvar.disabled, true);
    t.carregar();
    assert.equal(t.previa.hidden, false);
    assert.equal(t.salvar.disabled, false);
    assert.match(t.feedback.textContent, /pronta/);
});

test('rejeita tamanho, formato, dimensões excessivas e imagem ilegível', () => {
    const t = tela();
    t.selecionar({ size: 2097153, type: 'image/png' });
    assert.match(t.arquivo.validationMessage, /2 MB/);
    t.selecionar({ size: 100, type: 'image/svg+xml' });
    assert.match(t.arquivo.validationMessage, /JPG, PNG ou WebP/);
    t.selecionar();
    t.carregar(4097);
    assert.match(t.arquivo.validationMessage, /4096/);
    t.selecionar();
    t.leituras.at(-1).onerror();
    assert.match(t.arquivo.validationMessage, /abrir a imagem/);
    assert.equal(t.salvar.disabled, true);
    assert.equal(t.previa.hidden, true);
});

test('cancelar limpa a seleção, libera a prévia e devolve o foco ao botão', () => {
    const t = tela();
    t.selecionar(); t.carregar();
    t.cancelar.listeners.click();
    assert.equal(t.editor.open, false);
    assert.equal(t.arquivo.files.length, 0);
    assert.equal(t.previa.hidden, true);
    assert.equal(t.salvar.disabled, true);
    assert.equal(t.summary.focado, true);
    assert.deepEqual(t.revogadas, ['blob:foto-1']);
});

test('leitura antiga não restaura uma foto cancelada ou substituída', () => {
    const t = tela();
    t.selecionar();
    const antiga = t.leituras[0];
    t.selecionar({ size: 2097153, type: 'image/png' });
    t.carregar(200, 200, antiga);
    assert.equal(t.previa.hidden, true);
    assert.equal(t.salvar.disabled, true);
    assert.match(t.arquivo.validationMessage, /2 MB/);
});

test('envio fica bloqueado durante leitura e impede submissão duplicada', () => {
    const t = tela();
    let bloqueados = 0;
    const evento = { preventDefault() { bloqueados++; } };
    t.selecionar();
    t.form.listeners.submit(evento);
    assert.equal(bloqueados, 1);
    t.carregar();
    t.form.listeners.submit(evento);
    assert.equal(t.cancelar.disabled, true);
    assert.match(t.feedback.textContent, /Salvando/);
    t.form.listeners.submit(evento);
    assert.equal(bloqueados, 2);
});

test('script compartilhado continua funcionando nas páginas sem editor de foto', () => {
    vm.runInNewContext(codigo, { document: { querySelectorAll: () => [], querySelector: () => null } });
});
