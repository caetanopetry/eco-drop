/* Executar com node --test tests/theme.test.cjs. Não requer dependências. */
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const code = fs.readFileSync(path.join(root, 'frontend/assets/js/theme.js'), 'utf8');

function ambiente({salvo = null, escuro = false, bloqueado = false} = {}) {
    const callbacks = {}, sistema = {matches: escuro, addEventListener: (name, fn) => {callbacks.sistema = fn;}};
    const botao = {hidden: true, atributos: {}, setAttribute(name, value) {this.atributos[name] = value;}, addEventListener(name, fn) {this.click = fn;}};
    const html = {dataset: {}};
    const storage = {valor: salvo, getItem() {if (bloqueado) throw Error('bloqueado'); return this.valor;}, setItem(key, value) {if (bloqueado) throw Error('bloqueado'); this.valor = value;}};
    const document = {documentElement: html, querySelectorAll: () => [botao], addEventListener: (name, fn) => {callbacks[name] = fn;}};
    vm.runInNewContext(code, {document, localStorage: storage, window: {matchMedia: () => sistema, addEventListener: (name, fn) => {callbacks[name] = fn;}}});
    return {html, botao, storage, callbacks, sistema};
}

test('primeira visita aplica o sistema antes de DOMContentLoaded', () => {
    assert.equal(ambiente({escuro:true}).html.dataset.theme, 'dark');
    assert.equal(ambiente().html.dataset.theme, 'light');
});
test('preferência salva prevalece e o botão atualiza persistência e acessibilidade', () => {
    const a = ambiente({salvo:'light', escuro:true});
    assert.equal(a.html.dataset.theme, 'light');
    a.callbacks.DOMContentLoaded();
    assert.equal(a.botao.hidden, false);
    a.botao.click();
    assert.equal(a.html.dataset.theme, 'dark');
    assert.equal(a.storage.valor, 'dark');
    assert.equal(a.botao.atributos['aria-label'], 'Ativar tema claro');
    assert.equal(a.botao.atributos['aria-pressed'], 'true');
    a.botao.click();
    assert.equal(a.storage.valor, 'light');
});
test('armazenamento bloqueado e valor inválido não impedem a alternância', () => {
    for (const options of [{bloqueado:true, escuro:true}, {salvo:'invalid', escuro:true}]) {
        const a = ambiente(options);
        assert.equal(a.html.dataset.theme, 'dark');
        a.callbacks.DOMContentLoaded();
        a.botao.click();
        assert.equal(a.html.dataset.theme, 'light');
    }
});
test('mudanças do sistema só são seguidas antes de uma escolha explícita', () => {
    const a = ambiente();
    a.sistema.matches = true;
    a.callbacks.sistema();
    assert.equal(a.html.dataset.theme, 'dark');
    a.callbacks.DOMContentLoaded(); a.botao.click();
    a.callbacks.sistema();
    assert.equal(a.html.dataset.theme, 'light');
});
test('abas sincronizam preferências e remoção restaura preferência do sistema', () => {
    const a = ambiente({escuro:true});
    a.callbacks.storage({key:'ecodrop-theme',newValue:'light'});
    assert.equal(a.html.dataset.theme, 'light');
    a.callbacks.storage({key:'ecodrop-theme',newValue:null});
    assert.equal(a.html.dataset.theme, 'dark');
});

const css = fs.readFileSync(path.join(root, 'frontend/assets/css/variables.css'), 'utf8');
const dark = css.slice(css.indexOf('html[data-theme="dark"]'));
const vars = Object.fromEntries([...css.slice(0, css.indexOf('html[data-theme="dark"]')).matchAll(/(--[\w-]+):\s*([^;]+);/g)].map(m => [m[1], m[2]]));
const darkVars = {...vars, ...Object.fromEntries([...dark.matchAll(/(--[\w-]+):\s*([^;]+);/g)].map(m => [m[1], m[2]]))};
function resolve(name, seen = []) {
    assert(!seen.includes(name), `Variável circular: ${name}`);
    assert(darkVars[name], `Variável ausente: ${name}`);
    return darkVars[name].replace(/var\((--[\w-]+)\)/g, (_, dep) => resolve(dep, [...seen,name]));
}
function lum(hex) {
    const values = hex.slice(1).match(/../g).slice(0,3).map(h => parseInt(h,16) / 255).map(v => v <= .04045 ? v/12.92 : ((v+.055)/1.055)**2.4);
    return .2126*values[0] + .7152*values[1] + .0722*values[2];
}
function contraste(fg,bg) { const a = [lum(fg),lum(bg)].sort((a,b)=>b-a); return (a[0]+.05)/(a[1]+.05); }
test('tokens escuros não contêm referências circulares ou branco/preto puros', () => {
    for (const name of Object.keys(darkVars)) {
        const color = resolve(name);
        if (/^#[\da-f]{6}$/i.test(color)) assert(!['#ffffff','#000000'].includes(color.toLowerCase()));
    }
});
test('texto normal atende 4.5:1 nas superfícies efetivamente usadas', () => {
    for (const bg of ['--bg','--sidebar-bg','--surface','--surface-active','--surface-hover']) {
        for (const fg of ['--text','--text-muted','--text-menu','--accent']) {
            assert(contraste(resolve(fg),resolve(bg)) >= 4.5, `${fg} / ${bg}`);
        }
    }
    for (const [fg,bg] of [['--accent-text-on','--accent'],['--accent-text-on','--accent-hover'],['--accent','--chip-bg'],['--erro','--erro-fundo'],['--plastic-text','--plastic-bg'],['--paper-text','--paper-bg'],['--oil-text','--oil-bg']]) {
        assert(contraste(resolve(fg),resolve(bg)) >= 4.5, `${fg} / ${bg}`);
    }
    assert(contraste(resolve('--chart-bar'),resolve('--surface')) >= 3, 'Contraste das barras do gráfico');
});
test('as cores dos componentes estão centralizadas nos tokens', () => {
    const dir = path.join(root, 'frontend/assets/css');
    for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.css') && f !== 'variables.css')) {
        assert(!/#[\da-f]{3,8}\b|rgba?\(/i.test(fs.readFileSync(path.join(dir,f),'utf8')), f);
    }
});
