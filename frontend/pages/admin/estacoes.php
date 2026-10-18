<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminEstacoesRepository.php';
$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
$paginaAtual = 'estacoes';
header('Cache-Control: no-store');
$dados = null;
try {
    $dados = (new AdminEstacoesRepository())->visaoGeral();
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha ao consultar estações: ' . $e->getMessage());
    http_response_code(503);
}
$esc = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$numero = static fn($valor): string => number_format((float) $valor, 3, ',', '.');
$data = static fn($valor): string => $valor ? date('d/m/Y H:i', strtotime($valor)) : 'Nenhum descarte';
$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$rotulos = ['ativa' => 'Ativa', 'inativa' => 'Inativa', 'manutencao' => 'Em manutenção'];
if (!isset($rotulos[$status])) $status = '';
$todas = $dados['estacoes'] ?? [];
$filtradas = array_filter($todas, static fn($e) => ($status === '' || $e['status'] === $status)
    && ($busca === '' || mb_stripos($e['nome'] . ' ' . $e['endereco'], $busca, 0, 'UTF-8') !== false));
$materiais = [];
foreach ($dados['materiais'] ?? [] as $m) $materiais[$m['estacao_id']][] = $m;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estações · EcoDrop Admin</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'admin', 'admin-estacoes', 'theme'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="layout-admin">
    <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>
    <main class="admin-conteudo estacoes-pagina">
        <header class="admin-cabecalho">
            <div><p class="estacoes-sobretitulo">GESTÃO DA REDE</p><h1>Estações</h1><p class="texto-suave">Cada ponto de coleta, uma nova oportunidade de transformar.</p></div>
            <div class="flex itens-centro gap-3"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?><span><?= $esc($usuario['nome']) ?></span><?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?></div>
        </header>
        <?php if ($dados === null): ?>
        <div class="estacoes-vazio" role="alert"><h2>Dados indisponíveis</h2><p>Não foi possível consultar as estações. Recarregue a página para tentar novamente.</p></div>
        <?php else: ?>
        <p class="estacoes-nota">Visão de acompanhamento · Todo o histórico registrado no banco. Volumes consideram apenas descartes válidos.</p>
        <section class="regua-indicadores" aria-label="Resumo de todas as estações">
            <?php foreach ([['Estações cadastradas', count($todas)], ['Estações ativas', count(array_filter($todas, static fn($e) => $e['status'] === 'ativa'))], ['Descartes registrados', array_sum(array_column($todas, 'registros'))], ['Sólidos recebidos · kg', $numero(array_sum(array_column($todas, 'kg')))], ['Líquidos recebidos · L', $numero(array_sum(array_column($todas, 'litros')))]] as [$rotulo, $valor]): ?>
            <div class="item"><p class="valor"><?= $esc($valor) ?></p><p class="rotulo"><?= $esc($rotulo) ?></p></div>
            <?php endforeach; ?>
        </section>
        <section aria-labelledby="rede-titulo">
            <div class="estacoes-titulo"><div><h2 id="rede-titulo">Nossa rede de estações</h2><p class="texto-suave">Acompanhe a operação e os materiais recebidos em cada endereço.</p></div><span><?= count($filtradas) ?> de <?= count($todas) ?> estações</span></div>
            <form class="estacoes-filtros" method="get" action="<?= Url::escapar('/admin/estacoes/') ?>">
                <label>Buscar estação<input type="search" name="busca" value="<?= $esc($busca) ?>" placeholder="Nome ou endereço"></label>
                <label>Status<select name="status"><option value="">Todos os status</option><?php foreach ($rotulos as $chave => $rotulo): ?><option value="<?= $chave ?>" <?= $status === $chave ? 'selected' : '' ?>><?= $rotulo ?></option><?php endforeach; ?></select></label>
                <button class="botao botao-primario" type="submit">Filtrar estações</button><a class="botao botao-secundario" href="<?= Url::escapar('/admin/estacoes/') ?>">Limpar</a>
            </form>
            <div class="estacoes-grade">
            <?php foreach ($filtradas as $e): ?>
                <article class="estacoes-card">
                    <div class="estacoes-titulo"><span class="estacoes-codigo">ESTAÇÃO #<?= (int) $e['id'] ?></span><span class="estacoes-status estacoes-status--<?= $esc($e['status']) ?>"><?= $esc($rotulos[$e['status']] ?? $e['status']) ?></span></div>
                    <h3><?= $esc($e['nome']) ?></h3><p class="texto-suave estacoes-endereco"><?= $esc($e['endereco'] ?: 'Endereço não informado') ?></p>
                    <div class="estacoes-metricas"><div><strong><?= (int) $e['registros'] ?></strong><span>descartes registrados</span></div><div><strong><?= $numero($e['kg']) ?> <small>kg</small></strong><span>sólidos recebidos</span></div><div><strong><?= $numero($e['litros']) ?> <small>L</small></strong><span>líquidos recebidos</span></div></div>
                    <p class="estacoes-nota"><?= (int) $e['validos'] ?> válidos · <?= (int) $e['registros'] - (int) $e['validos'] ?> inválidos · <?= number_format((int) $e['pontos'], 0, ',', '.') ?> pontos gerados</p>
                    <details><summary>Ver materiais recebidos</summary>
                        <?php if (empty($materiais[$e['id']])): ?><p class="texto-suave">Esta estação ainda não possui descartes válidos.</p><?php else: ?><ul class="estacoes-materiais"><?php foreach ($materiais[$e['id']] as $m): ?><li><span><?= $esc($m['nome']) ?><small><?= (int) $m['registros'] ?> descartes válidos</small></span><strong><?= $numero($m['quantidade']) ?> <?= $esc($m['unidade']) ?></strong></li><?php endforeach; ?></ul><?php endif; ?>
                    </details>
                    <footer>Último descarte: <?= $esc($data($e['ultimo'])) ?></footer>
                </article>
            <?php endforeach; ?>
            </div>
            <?php if (!$filtradas): ?><div class="estacoes-vazio"><h3><?= $todas ? 'Nenhuma estação encontrada' : 'Sua rede começa aqui' ?></h3><p><?= $todas ? 'Ajuste a busca ou limpe os filtros para ver todas as estações.' : 'As estações cadastradas aparecerão neste painel.' ?></p></div><?php endif; ?>
        </section>
        <section class="estacoes-historico" aria-labelledby="historico-titulo">
            <h2 id="historico-titulo">Últimos descartes da rede</h2><p class="texto-suave">Os 12 registros mais recentes de todas as estações, incluindo descartes inválidos.</p>
            <div class="tabela-wrapper" role="region" aria-label="Descartes recentes, tabela com rolagem horizontal" tabindex="0"><table class="tabela-ecodrop"><thead><tr><th>Data</th><th>Estação</th><th>Material</th><th>Quantidade</th><th>Resultado</th></tr></thead><tbody>
                <?php foreach ($dados['recentes'] as $d): ?><tr><td><?= $esc($data($d['criado_em'])) ?></td><td><?= $esc($d['estacao']) ?></td><td><?= $esc($d['material']) ?></td><td><?= $numero($d['quantidade']) ?> <?= $esc($d['unidade']) ?></td><td><?= $d['status'] === 'valido' ? 'Válido' : 'Inválido' ?></td></tr><?php endforeach; ?>
                <?php if (!$dados['recentes']): ?><tr><td colspan="5">Nenhum descarte registrado até o momento.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
