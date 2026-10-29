<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminParceirosRepository.php';

$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
header('Cache-Control: no-store');
$paginaAtual = 'parceiros';
$repo = new AdminParceirosRepository();
$erro = '';
$acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        $erro = 'Sua sessão do formulário expirou. Recarregue a página e tente novamente.';
    } else {
        try {
            if ($acao !== 'criar') {
                throw new InvalidArgumentException('Ação inválida.');
            }

            $repo->criar($_POST);
            $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : 'Parceiro';
            $_SESSION['parceiros_sucesso'] = 'Parceria com “' . $nome . '” cadastrada com sucesso.';
            header('Location: ' . Url::para('/admin/parceiros/'), true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $erro = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[EcoDrop] Falha ao cadastrar parceiro: ' . $e->getMessage());
            http_response_code(503);
            $erro = 'Não foi possível cadastrar o parceiro. Os dados preenchidos foram mantidos; tente novamente.';
        }
    }
}

$sucesso = $_SESSION['parceiros_sucesso'] ?? '';
unset($_SESSION['parceiros_sucesso']);
$esc = static fn(mixed $valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$textoPost = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
$valores = [
    'nome' => $acao === 'criar' ? $textoPost('nome') : '',
    'descricao' => $acao === 'criar' ? $textoPost('descricao') : '',
    'contato' => $acao === 'criar' ? $textoPost('contato') : '',
    'status' => $acao === 'criar' && isset(AdminParceirosRepository::STATUS[$textoPost('status')])
        ? $textoPost('status')
        : 'ativo',
];
$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$statusFiltro = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
if (!isset(AdminParceirosRepository::STATUS[$statusFiltro])) {
    $statusFiltro = '';
}

$parceiros = null;
try {
    $parceiros = $repo->listar();
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha ao consultar parceiros: ' . $e->getMessage());
    http_response_code(503);
    $erro = $erro ?: 'Não foi possível carregar os parceiros. Tente novamente em instantes.';
}

$filtrados = array_filter(
    $parceiros ?? [],
    static fn(array $parceiro): bool => ($statusFiltro === '' || $parceiro['status'] === $statusFiltro)
        && ($busca === '' || mb_stripos(
            $parceiro['nome'] . ' ' . ($parceiro['descricao'] ?? '') . ' ' . ($parceiro['contato'] ?? ''),
            $busca,
            0,
            'UTF-8'
        ) !== false)
);
$indicadores = [
    'total' => count($parceiros ?? []),
    'ativos' => count(array_filter($parceiros ?? [], static fn(array $p): bool => $p['status'] === 'ativo')),
    'inativos' => count(array_filter($parceiros ?? [], static fn(array $p): bool => $p['status'] === 'inativo')),
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parceiros · EcoDrop Admin</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'admin', 'admin-parceiros', 'theme'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="layout-admin">
    <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>
    <main class="admin-conteudo parceiros-pagina">
        <header class="admin-cabecalho">
            <div>
                <p class="parceiros-sobretitulo">CONEXÕES QUE TRANSFORMAM</p>
                <h1>Parceiros</h1>
                <p>Cadastre e consulte as organizações parceiras do EcoDrop.</p>
            </div>
            <div class="flex itens-centro gap-3">
                <?php require __DIR__ . '/../../components/theme/toggle.php'; ?>
                <span><?= $esc($usuario['nome']) ?></span>
                <?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?>
            </div>
        </header>

        <?php if ($erro): ?><p class="parceiros-mensagem parceiros-mensagem--erro" role="alert"><?= $esc($erro) ?></p><?php endif; ?>
        <?php if ($sucesso): ?><p class="parceiros-mensagem" role="status"><?= $esc($sucesso) ?></p><?php endif; ?>

        <?php if ($parceiros === null): ?>
            <div class="parceiros-vazio" role="alert">
                <h2>Dados indisponíveis</h2>
                <p>Não foi possível consultar os parceiros. Recarregue a página para tentar novamente.</p>
            </div>
        <?php else: ?>
            <section class="regua-indicadores parceiros-indicadores" aria-label="Resumo dos parceiros">
                <div class="item"><p class="valor"><?= $indicadores['total'] ?></p><p class="rotulo">Parceiros cadastrados</p></div>
                <div class="item"><p class="valor"><?= $indicadores['ativos'] ?></p><p class="rotulo">Ativos</p></div>
                <div class="item"><p class="valor"><?= $indicadores['inativos'] ?></p><p class="rotulo">Inativos</p></div>
            </section>

            <section class="parceiros-secao" aria-labelledby="parceiros-titulo">
                <div class="parceiros-intro">
                    <div>
                        <h2 id="parceiros-titulo">Parceiros cadastrados</h2>
                        <p>Organizações vinculadas ao projeto e disponíveis para as iniciativas do EcoDrop.</p>
                    </div>
                </div>

                <details class="parceiros-cadastro" id="novo-parceiro" <?= $acao === 'criar' && $erro !== '' ? 'open' : '' ?>>
                    <summary class="botao botao-primario">Registrar nova parceria</summary>
                    <div class="parceiros-form-painel">
                        <h3>Dados do parceiro</h3>
                        <form method="post" action="<?= Url::escapar('/admin/parceiros/') ?>" class="parceiros-form">
                            <?= Csrf::campoHtml() ?>
                            <input type="hidden" name="acao" value="criar">
                            <label>Nome do parceiro
                                <input name="nome" type="text" maxlength="150" required value="<?= $esc($valores['nome']) ?>" placeholder="Nome da organização ou negócio">
                            </label>
                            <label>Descrição
                                <textarea name="descricao" rows="3" maxlength="255" placeholder="Como a organização participa das iniciativas EcoDrop"><?= $esc($valores['descricao']) ?></textarea>
                            </label>
                            <label>Contato
                                <input name="contato" type="text" maxlength="150" value="<?= $esc($valores['contato']) ?>" placeholder="E-mail, telefone ou pessoa de contato">
                            </label>
                            <label>Disponibilidade
                                <select name="status">
                                    <?php foreach (AdminParceirosRepository::STATUS as $chave => $rotulo): ?>
                                    <option value="<?= $esc($chave) ?>" <?= $valores['status'] === $chave ? 'selected' : '' ?>><?= $esc($rotulo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button class="botao botao-primario parceiros-form-botao" type="submit">Cadastrar parceiro</button>
                        </form>
                    </div>
                </details>

                <form class="parceiros-filtros" method="get" action="<?= Url::escapar('/admin/parceiros/') ?>">
                    <label>Buscar parceiro
                        <input type="search" name="busca" value="<?= $esc($busca) ?>" placeholder="Nome, descrição ou contato">
                    </label>
                    <label>Status
                        <select name="status">
                            <option value="">Todos os status</option>
                            <?php foreach (AdminParceirosRepository::STATUS as $chave => $rotulo): ?>
                            <option value="<?= $esc($chave) ?>" <?= $statusFiltro === $chave ? 'selected' : '' ?>><?= $esc($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="botao botao-primario" type="submit">Filtrar parceiros</button>
                    <a class="botao botao-secundario" href="<?= Url::escapar('/admin/parceiros/') ?>">Limpar</a>
                </form>

                <p class="parceiros-nota"><?= count($filtrados) ?> de <?= count($parceiros) ?> parceiros</p>
                <div class="parceiros-rede">
                    <?php foreach ($filtrados as $parceiro): ?>
                    <article class="parceiros-card">
                        <div class="parceiros-card-topo">
                            <span class="parceiros-status parceiros-status--<?= $esc($parceiro['status']) ?>"><?= $esc(AdminParceirosRepository::STATUS[$parceiro['status']] ?? 'Inativo') ?></span>
                            <span class="parceiros-data">Desde <?= $esc(date('d/m/Y', strtotime($parceiro['criado_em']))) ?></span>
                        </div>
                        <h3><?= $esc($parceiro['nome']) ?></h3>
                        <p><?= $esc($parceiro['descricao'] ?: 'Descrição não informada.') ?></p>
                        <p class="parceiros-contato"><strong>Contato:</strong> <?= $esc($parceiro['contato'] ?: 'Não informado') ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php if (!$filtrados): ?>
                    <div class="parceiros-vazio">
                        <h3><?= $parceiros ? 'Nenhum parceiro encontrado' : 'Nenhum parceiro cadastrado' ?></h3>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
