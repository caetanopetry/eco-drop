const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const codigo = fs.readFileSync(path.join(__dirname, '../frontend/assets/js/admin-usuarios.js'), 'utf8');
function tela() {
    const nodes = {};
    const el = id => nodes[id] ||= {value:'',innerHTML:'',textContent:'',listeners:{},addEventListener(t,f){this.listeners[t]=f;},showModal(){this.open=true;},close(){this.open=false;}};
    const contas = Array.from({length:8}, (_,i)=>[i+1,i===0 ? 'João <script>alert(1)</script>' : `Conta ${i+1}`,`conta${i+1}@example.com`,i===7 ? 'Administrador':'Usuário',i===6 ? 'Bloqueado':'Ativo',i===0 ? 'Online':'Offline','08/10/2026','Sem registro','Não vinculada',0,0]);
    el('usuarios-dados').textContent = JSON.stringify({contas,indicadores:{total:8,online:1,novas:8,ativas:7,bloqueadas:1,inativas:0},grafico:[{dia:'08/10',total:8}],atividade:[]});
    vm.runInNewContext(codigo,{document:{getElementById:el}});
    return el;
}
test('lista pagina as contas sem perder itens',()=>{
    const el = tela();
    assert.match(el('resultado').textContent,/1–6 de 8/);
    el('proxima').listeners.click();
    assert.match(el('resultado').textContent,/7–8 de 8/);
    assert.equal(el('proxima').disabled,true);
    el('anterior').listeners.click();
    assert.match(el('resultado').textContent,/1–6 de 8/);
});
test('busca ignora acentos e filtros combinados oferecem estado vazio',()=>{
    const el = tela();
    el('busca').value='joao'; el('busca').listeners.input();
    assert.match(el('resultado').textContent,/de 1 contas/);
    el('perfil').value='Administrador'; el('perfil').listeners.change();
    assert.match(el('contas').innerHTML,/Nenhuma conta encontrada/);
    el('limpar').listeners.click();
    assert.match(el('resultado').textContent,/de 8 contas/);
});
test('nomes do banco são escapados na lista, avatar e detalhes',()=>{
    const el = tela();
    assert(!el('contas').innerHTML.includes('<script>'));
    el('contas').listeners.click({target:{closest:()=>({dataset:{id:1}})}});
    assert.equal(el('detalhes').open,true);
    assert(!el('detalhes-conteudo').innerHTML.includes('<script>'));
    assert.match(el('detalhes-conteudo').innerHTML,/&lt;script&gt;/);
    el('fechar').listeners.click();
    assert.equal(el('detalhes').open,false);
});
