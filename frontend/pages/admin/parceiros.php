<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/repositories/PropostasParceriaRepository.php';
$usuario=Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
header('Cache-Control: no-store');
$paginaAtual='parceiros';
$repo=new PropostasParceriaRepository();
$erro=''; $dados=null; $parceiros=[];
$acao=is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!Csrf::validar(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403); $erro='Sua sessão do formulário expirou. Recarregue a página e tente novamente.';
    } else {
        try {
            if ($acao==='receber') $repo->receber($_POST);
            elseif ($acao==='avaliar') {
                $id=filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || !is_string($_POST['status'] ?? null) || !is_string($_POST['notas'] ?? null)) throw new InvalidArgumentException('Avaliação inválida.');
                $repo->avaliar($id,$_POST['status'],$_POST['notas'],(int)$usuario['id']);
            } else throw new InvalidArgumentException('Ação inválida.');
            $_SESSION['parceiros_sucesso']=$acao==='receber' ? 'Proposta registrada com sucesso.' : 'Avaliação salva com sucesso.';
            header('Location: '.Url::para('/admin/parceiros/'),true,303); exit;
        } catch (InvalidArgumentException $e) { http_response_code(422); $erro=$e->getMessage(); }
        catch (Throwable $e) { error_log('[EcoDrop] Falha ao salvar proposta: '.$e->getMessage()); http_response_code(503); $erro='Não foi possível salvar. Os dados preenchidos foram mantidos; tente novamente.'; }
    }
}
$sucesso=$_SESSION['parceiros_sucesso'] ?? ''; unset($_SESSION['parceiros_sucesso']);
try {
    $dados=$repo->listar();
    $parceiros=Database::conexao()->query('SELECT nome,descricao,contato,status FROM parceiros ORDER BY nome')->fetchAll();
} catch (Throwable $e) { error_log('[EcoDrop] Falha ao consultar parcerias: '.$e->getMessage()); http_response_code(503); $erro=$erro ?: 'Não foi possível carregar as parcerias. Tente novamente em instantes.'; }
$esc=static fn($v): string => htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$textoPost=static fn($campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
$busca=is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$status=is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
if (!isset(PropostasParceriaRepository::STATUS[$status])) $status='';
$filtradas=array_filter($dados ?? [],static fn($p) => ($status==='' || $p['status']===$status) && ($busca==='' || mb_stripos($p['organizacao'].' '.$p['responsavel'].' '.$p['email'].' '.$p['cidade'],$busca,0,'UTF-8')!==false));
$campos=['organizacao'=>'Organização / nome do negócio','responsavel'=>'Responsável','email'=>'E-mail','telefone'=>'Telefone / WhatsApp','cidade'=>'Cidade / UF','segmento'=>'Segmento de atuação','tipo'=>'Tipo de parceria pretendida'];
?>
<!doctype html>
<html lang="pt-BR"><head>
    <meta charset="utf-8">
    <?php require __DIR__.'/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parceiros · EcoDrop Admin</title>
    <?php require __DIR__.'/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables','global','components','admin','admin-parceiros','theme'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/'.$css.'.css') ?>?v=<?= filemtime(__DIR__.'/../../assets/css/'.$css.'.css') ?>">
    <?php endforeach; ?>
</head><body><div class="layout-admin">
<?php include __DIR__.'/../../components/navbar/navbar-admin.php'; ?>
<main class="admin-conteudo parceiros-pagina">
    <header class="admin-cabecalho"><div><p class="parceiros-sobretitulo">CONEXÕES QUE TRANSFORMAM</p><h1>Parceiros</h1><p>Conheça as propostas e acompanhe cada nova possibilidade de parceria.</p></div><div class="flex itens-centro gap-3"><?php require __DIR__.'/../../components/theme/toggle.php'; ?><span><?= $esc($usuario['nome']) ?></span><a class="botao botao-secundario" href="<?= Url::escapar('/app/logout/') ?>">Sair</a></div></header>
    <?php if ($erro): ?><p class="parceiros-mensagem" role="alert"><?= $esc($erro) ?></p><?php endif; ?>
    <?php if ($sucesso): ?><p class="parceiros-mensagem" role="status"><?= $esc($sucesso) ?></p><?php endif; ?>
    <?php if ($dados!==null): ?>
    <section class="regua-indicadores" aria-label="Resumo de todas as propostas">
    <?php foreach (['total'=>'Propostas recebidas']+PropostasParceriaRepository::STATUS as $chave=>$rotulo): $total=$chave==='total' ? count($dados) : count(array_filter($dados,static fn($p)=>$p['status']===$chave)); ?>
        <div class="item"><p class="valor"><?= $total ?></p><p class="rotulo"><?= $chave==='total' ? $rotulo : ['nova'=>'Novas propostas','em_analise'=>'Em análise','aprovada'=>'Aprovadas','recusada'=>'Recusadas'][$chave] ?></p></div>
    <?php endforeach; ?></section>
    <div class="parceiros-intro"><div><h2>Propostas de parceria</h2><p>Consulte os formulários, avalie as ideias e registre sua decisão.</p></div><a class="botao botao-primario" href="#registrar-proposta">Registrar proposta</a></div>
    <p class="parceiros-nota">O formulário público “Seja um Parceiro” será conectado em uma próxima etapa. Por enquanto, registre aqui propostas recebidas por outros canais.</p>
    <form class="parceiros-filtros" method="get" action="<?= Url::escapar('/admin/parceiros/') ?>"><label>Buscar proposta<input type="search" name="busca" value="<?= $esc($busca) ?>" placeholder="Organização, responsável, e-mail ou cidade"></label><label>Status<select name="status"><option value="">Todos os status</option><?php foreach (PropostasParceriaRepository::STATUS as $chave=>$rotulo): ?><option value="<?= $chave ?>" <?= $status===$chave ? 'selected' : '' ?>><?= $rotulo ?></option><?php endforeach; ?></select></label><button class="botao botao-primario">Filtrar</button><a class="botao botao-secundario" href="<?= Url::escapar('/admin/parceiros/') ?>">Limpar</a></form>
    <p class="parceiros-nota"><?= count($filtradas) ?> de <?= count($dados) ?> propostas · Mais recentes primeiro</p>
    <div class="parceiros-lista">
    <?php foreach ($filtradas as $p): $falhou=$erro && $acao==='avaliar' && $textoPost('id')===(string)$p['id']; ?>
        <article class="parceiros-card"><div class="parceiros-intro"><span class="parceiros-sobretitulo">PROPOSTA #<?= (int)$p['id'] ?> · <?= date('d/m/Y H:i',strtotime($p['criado_em'])) ?></span><span class="parceiros-status parceiros-status--<?= $esc($p['status']) ?>"><?= $esc(PropostasParceriaRepository::STATUS[$p['status']]) ?></span></div><h3><?= $esc($p['organizacao']) ?></h3><p><?= $esc($p['segmento']) ?> · <?= $esc($p['cidade']) ?></p><p><strong><?= $esc($p['responsavel']) ?></strong> · <?= $esc($p['email']) ?></p>
        <details <?= $falhou ? 'open' : '' ?>><summary>Ver formulário e avaliar proposta</summary>
            <dl class="parceiros-dados"><?php foreach ($campos as $campo=>$rotulo): ?><div><dt><?= $rotulo ?></dt><dd><?= $esc($p[$campo]) ?></dd></div><?php endforeach; ?></dl>
            <h4>Proposta apresentada</h4><p class="parceiros-texto"><?= $esc($p['proposta']) ?></p>
            <form method="post" action="<?= Url::escapar('/admin/parceiros/') ?>" class="parceiros-avaliacao">
                <?= Csrf::campoHtml() ?><input type="hidden" name="acao" value="avaliar"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <h4>Avaliação interna</h4><label>Status da proposta<select name="status" required><?php foreach (PropostasParceriaRepository::STATUS as $chave=>$rotulo): ?><option value="<?= $chave ?>" <?= ($falhou ? $textoPost('status') : $p['status'])===$chave ? 'selected' : '' ?>><?= $rotulo ?></option><?php endforeach; ?></select></label>
                <label>Observações internas<textarea name="notas" rows="4" maxlength="5000" placeholder="Pontos a avaliar, próximos passos e motivo da decisão (até 5.000 caracteres)"><?= $esc($falhou ? $textoPost('notas') : $p['notas']) ?></textarea></label>
                <p class="parceiros-nota">A aprovação registra a decisão da equipe. A ativação da parceria será tratada separadamente. Nenhuma mensagem é enviada ao candidato.</p>
                <button class="botao botao-primario">Salvar avaliação</button>
                <?php if ($p['avaliado_em']): ?><p class="parceiros-nota">Última avaliação: <?= date('d/m/Y H:i',strtotime($p['avaliado_em'])) ?> · <?= $esc($p['avaliador'] ?? 'Administrador removido') ?></p><?php endif; ?>
            </form>
        </details></article>
    <?php endforeach; ?></div>
    <?php if (!$filtradas): ?><div class="parceiros-vazio"><h3><?= $dados ? 'Nenhuma proposta encontrada' : 'Novas conexões começam aqui' ?></h3><p><?= $dados ? 'Ajuste a busca ou limpe os filtros.' : 'Ainda não há formulários recebidos. As propostas aparecerão aqui para avaliação.' ?></p></div><?php endif; ?>
    <section class="parceiros-secao" id="registrar-proposta"><details class="parceiros-card" <?= $erro && $acao==='receber' ? 'open' : '' ?>><summary>Registrar uma proposta recebida</summary><p>Todos os campos são obrigatórios. Use este formulário para propostas recebidas pela equipe.</p>
        <form method="post" action="<?= Url::escapar('/admin/parceiros/') ?>"><?= Csrf::campoHtml() ?><input type="hidden" name="acao" value="receber"><div class="parceiros-dados">
        <?php foreach ($campos as $campo=>$rotulo): ?><label><?= $rotulo ?><input name="<?= $campo ?>" type="<?= $campo==='email' ? 'email' : ($campo==='telefone' ? 'tel' : 'text') ?>" maxlength="<?= PropostasParceriaRepository::CAMPOS[$campo] ?>" required value="<?= $esc($acao==='receber' ? $textoPost($campo) : '') ?>"></label><?php endforeach; ?>
        </div><label>Descrição da proposta<textarea name="proposta" rows="5" maxlength="5000" required placeholder="Como a organização quer colaborar? Descreva benefícios, recursos e abrangência (até 5.000 caracteres). "><?= $esc($acao==='receber' ? $textoPost('proposta') : '') ?></textarea></label><button class="botao botao-primario">Registrar proposta</button></form>
    </details></section>
    <section class="parceiros-secao"><h2>Parceiros cadastrados</h2><p>Organizações já vinculadas ao projeto, independentes das propostas em avaliação.</p><div class="parceiros-rede">
    <?php foreach ($parceiros as $p): ?><article class="parceiros-card"><span class="parceiros-status"><?= $p['status']==='ativo' ? 'Ativo' : 'Inativo' ?></span><h3><?= $esc($p['nome']) ?></h3><p><?= $esc($p['descricao'] ?: 'Descrição não informada') ?></p><p class="parceiros-nota">Contato: <?= $esc($p['contato'] ?: 'Não informado') ?></p></article><?php endforeach; ?>
    <?php if (!$parceiros): ?><p>Nenhum parceiro cadastrado.</p><?php endif; ?></div></section>
    <?php endif; ?>
</main></div></body></html>
