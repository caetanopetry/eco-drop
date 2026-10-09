'use strict';
(() => {
    const dados = JSON.parse(document.getElementById('usuarios-dados').textContent);
    if (!dados) return;
    const contas = dados.contas;
    const el = id => document.getElementById(id);
    const escape = value => String(value).replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
    const normalizar = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const badge = value => `<span class="usuarios-badge ${normalizar(value).replace(/ /g, '-')}">${escape(value)}</span>`;
    const m = dados.indicadores;
    el('indicadores').innerHTML = [
        ['Contas criadas', m.total, 'Inclui todos os perfis'],
        ['Online agora', m.online, 'Atividade nos últimos 5 min'],
        ['Novas em 7 dias', m.novas, 'Últimos 7 dias, incluindo hoje'],
        ['Contas ativas', m.ativas, 'Habilitadas para acesso'],
        ['Contas bloqueadas', m.bloqueadas, `${m.inativas} conta(s) inativa(s)`]
    ].map(([label, value, note]) => `<article class="usuarios-metrica"><p>${label}</p><strong>${value}</strong><small>${note}</small></article>`).join('');
    const maximo = Math.max(1, ...dados.grafico.map(d => d.total));
    el('grafico').innerHTML = dados.grafico.map(d => `<div class="usuarios-coluna"><strong>${d.total}</strong><div class="usuarios-barra" style="height:${d.total / maximo * 100}px"></div><span>${escape(d.dia)}</span></div>`).join('');
    const eventos = {cadastro: 'criou uma conta', login: 'entrou na plataforma', logout: 'encerrou a sessão'};
    el('atividade').innerHTML = dados.atividade.map(a => `<li><span class="usuarios-ponto ${a.tipo === 'logout' ? 'ponto-neutro' : ''}"></span><div><strong>${escape(a.nome)} ${eventos[a.tipo]}</strong><small>${escape(a.ocorrido_em.replace(/^(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1'))}</small></div></li>`).join('') || '<li>Nenhuma atividade registrada.</li>';
    let pagina = 1;
    const tamanho = 6;
    function render() {
        const busca = normalizar(el('busca').value.trim());
        const filtradas = contas.filter(c => normalizar(`${c[0]} ${c[1]} ${c[2]}`).includes(busca) && (!el('status').value || c[4] === el('status').value) && (!el('acesso').value || c[5] === el('acesso').value) && (!el('perfil').value || c[3] === el('perfil').value));
        const inicio = (pagina - 1) * tamanho;
        el('contas').innerHTML = filtradas.slice(inicio, inicio + tamanho).map(c => `<tr><td><div class="usuarios-conta"><span class="usuarios-avatar" aria-hidden="true">${escape(c[1].trim().split(/\s+/).map(n => n[0] || '').slice(0, 2).join(''))}</span><div><strong>${escape(c[1])}</strong><small>${escape(c[2])} · #${c[0]}</small></div></div></td><td>${escape(c[3])}</td><td>${badge(c[4])}</td><td>${badge(c[5])}</td><td>${escape(c[6])}</td><td>${escape(c[7])}</td><td><button type="button" class="usuarios-ver" data-id="${c[0]}" aria-label="Ver detalhes de ${escape(c[1])}">Ver conta</button></td></tr>`).join('') || '<tr><td colspan="7">Nenhuma conta encontrada. Experimente ajustar os filtros.</td></tr>';
        el('resultado').textContent = filtradas.length ? `${inicio + 1}–${Math.min(inicio + tamanho, filtradas.length)} de ${filtradas.length} contas · Página ${pagina} de ${Math.ceil(filtradas.length / tamanho)}` : '0 contas encontradas';
        el('anterior').disabled = pagina === 1;
        el('proxima').disabled = inicio + tamanho >= filtradas.length;
    }
    ['busca', 'status', 'acesso', 'perfil'].forEach(id => el(id).addEventListener(id === 'busca' ? 'input' : 'change', () => { pagina = 1; render(); }));
    el('limpar').addEventListener('click', () => { ['busca', 'status', 'acesso', 'perfil'].forEach(id => { el(id).value = ''; }); pagina = 1; render(); });
    el('anterior').addEventListener('click', () => { pagina--; render(); });
    el('proxima').addEventListener('click', () => { pagina++; render(); });
    el('contas').addEventListener('click', event => {
        const button = event.target.closest('[data-id]');
        if (!button) return;
        const c = contas.find(conta => conta[0] === Number(button.dataset.id));
        el('detalhes-conteudo').innerHTML = `<dl>${[['Nome', c[1]], ['ID', `#${c[0]}`], ['E-mail', c[2]], ['Perfil', c[3]], ['Status', c[4]], ['Acesso', c[5]], ['Criada em', c[6]], ['Último acesso', c[7]], ['Conta Google', c[8]], ['Saldo de pontos', c[9]], ['Descartes realizados', c[10]]].map(([label, value]) => `<dt>${label}</dt><dd>${escape(value)}</dd>`).join('')}</dl>`;
        el('detalhes').showModal();
    });
    el('fechar').addEventListener('click', () => el('detalhes').close());
    render();
})();
