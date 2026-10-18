const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const codigo = fs.readFileSync(path.join(__dirname, '../frontend/assets/js/conta-nome.js'), 'utf8');
function tela() {
    const node = () => ({ listeners: {}, textContent: '', addEventListener(t, f) { this.listeners[t] = f; }, focus() { this.focado = true; }, setAttribute(k, v) { this[k] = v; }, removeAttribute(k) { delete this[k]; } });
    const editor = node(), form = node(), nome = node(), salvar = node(), cancelar = node(), feedback = node(), summary = node(), window = node();
    nome.value = 'Ana Silva'; nome.dataset = { nomeAtual: 'Ana Silva' };
    nome.setCustomValidity = mensagem => { nome.erro = mensagem; };
    nome.reportValidity = () => { nome.reportado = true; };
    editor.open = false;
    editor.querySelector = s => ({ form, summary })[s];
    form.querySelector = s => ({ '#nome': nome, 'button[type="submit"]': salvar, '[data-cancelar-nome]': cancelar, '#nome-feedback': feedback })[s];
    vm.runInNewContext(codigo, { document: { querySelector: () => editor }, window });
    const enviar = () => { let bloqueado = false; form.listeners.submit({ preventDefault() { bloqueado = true; } }); return bloqueado; };
    return { editor, nome, salvar, cancelar, feedback, summary, window, enviar };
}
test('normaliza espaços e evita envios duplicados', () => {
    const t = tela(); t.nome.value = '  João\u00a0  D’Ávila  ';
    assert.equal(t.enviar(), false);
    assert.equal(t.nome.value, 'João D’Ávila');
    assert.equal(t.salvar.disabled, true);
    assert.equal(t.enviar(), true);
    t.window.listeners.pageshow();
    assert.equal(t.salvar.disabled, false);
});
test('rejeita nome vazio ou maior que 150 caracteres', () => {
    const t = tela();
    for (const nome of [' \u00a0 ', 'Á'.repeat(151)]) {
        t.nome.value = nome;
        assert.equal(t.enviar(), true);
        assert.ok(t.nome.erro);
        assert.equal(t.nome.reportado, true);
    }
});
test('nome sem alteração informa que já está atualizado', () => {
    const t = tela(); t.nome.value = ' Ana  Silva ';
    assert.equal(t.enviar(), true);
    assert.match(t.feedback.textContent, /já está atualizado/);
});
test('cancelar restaura nome salvo, fecha editor e devolve foco', () => {
    const t = tela(); t.editor.open = true; t.nome.value = 'Outro nome';
    t.cancelar.listeners.click({ preventDefault() {} });
    assert.equal(t.nome.value, 'Ana Silva');
    assert.equal(t.editor.open, false);
    assert.equal(t.summary.focado, true);
});
test('fechar e reabrir descarta edição e posiciona foco no campo', () => {
    const t = tela(); t.nome.value = 'Descartar';
    t.editor.listeners.toggle();
    assert.equal(t.nome.value, 'Ana Silva');
    t.editor.open = true; t.editor.listeners.toggle();
    assert.equal(t.nome.focado, true);
});
