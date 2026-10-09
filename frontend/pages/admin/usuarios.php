<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminUsuariosRepository.php';
$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
$paginaAtual = 'usuarios';
header('Cache-Control: no-store');
$dados = null;
try {
    $dados = (new AdminUsuariosRepository())->visaoGeral();
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha na consulta de usuários: ' . $e->getMessage());
    http_response_code(503);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuários · EcoDrop Admin</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <?php foreach (['variables', 'global', 'components', 'admin', 'admin-usuarios'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
    <script src="<?= Url::escapar('/frontend/assets/js/admin-usuarios.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body>
<div class="layout-admin">
    <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>
    <main class="admin-conteudo usuarios-pagina">
        <header class="admin-cabecalho">
            <div><p class="usuarios-sobretitulo">GESTÃO DE CONTAS</p><h1>Usuários</h1><p class="texto-suave">Conheça sua comunidade e acompanhe os acessos.</p></div>
            <div class="flex itens-centro gap-3"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?><span><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></span><a class="botao botao-secundario" href="<?= Url::escapar('/app/logout/') ?>">Sair</a></div>
        </header>
        <div class="usuarios-aviso"><strong><?= $dados === null ? 'Dados indisponíveis' : 'Dados do banco' ?></strong><span><?= $dados === null ? 'Não foi possível consultar as contas. Tente novamente em instantes.' : 'Atualizado em ' . htmlspecialchars($dados['atualizado'], ENT_QUOTES, 'UTF-8') . ' (Brasília). Recarregue a página para atualizar.' ?></span></div>
        <noscript><p>Ative o JavaScript para visualizar e filtrar as contas.</p></noscript>
        <section class="usuarios-indicadores" aria-label="Resumo das contas" id="indicadores"></section>
        <div class="usuarios-resumo">
            <section class="usuarios-card"><h2>Cadastros nos últimos 7 dias</h2><p class="texto-suave">Crescimento da comunidade</p><div class="usuarios-grafico" id="grafico" aria-label="Cadastros por dia"></div></section>
            <section class="usuarios-card"><h2>Atividade recente</h2><p class="texto-suave">Últimos cadastros, logins e saídas registrados</p><ul class="usuarios-atividade" id="atividade"></ul></section>
        </div>
        <section class="usuarios-card">
            <div class="usuarios-lista-cabecalho"><div><h2>Todas as contas</h2><p class="texto-suave">Usuários e administradores em um só lugar.</p></div><button class="botao botao-secundario" id="limpar" type="button">Limpar filtros</button></div>
            <div class="usuarios-filtros">
                <label>Buscar conta<input id="busca" type="search" placeholder="Nome, e-mail ou ID"></label>
                <label>Status<select id="status"><option value="">Todos os status</option><option>Ativo</option><option>Inativo</option><option>Bloqueado</option></select></label>
                <label>Acesso<select id="acesso"><option value="">Todos os acessos</option><option>Online</option><option>Offline</option><option>Sem registro</option></select></label>
                <label>Perfil<select id="perfil"><option value="">Todos os perfis</option><option>Usuário</option><option>Administrador</option></select></label>
            </div>
            <p class="usuarios-legenda">Online: sessão aberta com atividade nos últimos 5 minutos. Status ativo indica uma conta habilitada. Os acessos passam a ser registrados a partir desta atualização; contas sem histórico aparecem como “Sem registro”.</p>
            <div class="tabela-wrapper" role="region" aria-label="Lista de contas" tabindex="0"><table class="tabela-ecodrop usuarios-tabela"><thead><tr><th scope="col">Conta</th><th scope="col">Perfil</th><th scope="col">Status</th><th scope="col">Acesso</th><th scope="col">Criada em</th><th scope="col">Último acesso</th><th scope="col">Detalhes</th></tr></thead><tbody id="contas"></tbody></table></div>
            <div class="usuarios-paginacao"><span id="resultado" role="status" aria-live="polite"></span><div><button type="button" class="botao botao-secundario" id="anterior">Anterior</button><button type="button" class="botao botao-secundario" id="proxima">Próxima</button></div></div>
        </section>
        <dialog id="detalhes" class="usuarios-dialog"><div class="usuarios-lista-cabecalho"><h2>Detalhes da conta</h2><button type="button" class="botao botao-secundario" id="fechar">Fechar</button></div><div id="detalhes-conteudo"></div><p class="usuarios-legenda">Saldo calculado pelas movimentações de pontos registradas.</p></dialog>
    </main>
</div>
<script type="application/json" id="usuarios-dados"><?= json_encode($dados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?></script>
</body>
</html>
