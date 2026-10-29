<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminRecompensasRepository.php';

$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
$paginaAtual = 'recompensas';
header('Cache-Control: no-store');

$repo = new AdminRecompensasRepository();
$erro = '';
$acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        $erro = 'Sua sessão do formulário expirou. Recarregue a página e tente novamente.';
    } else {
        try {
            if ($acao === 'criar') {
                $repo->criar($_POST);
                $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : 'Recompensa';
                $_SESSION['recompensas_sucesso'] = 'Recompensa “' . $nome . '” criada com sucesso.';
                if (($_POST['status'] ?? 'ativo') === 'ativo') {
                    $_SESSION['recompensas_sucesso'] .= ' Ela já está visível para os usuários.';
                }
                header('Location: ' . Url::para('/admin/recompensas/'), true, 303);
                exit;
            }

            if ($acao === 'status') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) {
                    throw new InvalidArgumentException('Recompensa inválida.');
                }
                $novoStatus = $repo->alternarStatus($id);
                $_SESSION['recompensas_sucesso'] = $novoStatus === 'ativo'
                    ? 'Recompensa publicada. Ela já aparece no catálogo do app.'
                    : 'Recompensa pausada. Ela deixou de aparecer no catálogo do app.';
                header('Location: ' . Url::para('/admin/recompensas/'), true, 303);
                exit;
            }

            throw new InvalidArgumentException('Ação inválida.');
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $erro = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[EcoDrop] Falha ao salvar recompensa: ' . $e->getMessage());
            http_response_code(503);
            $erro = 'Não foi possível salvar a recompensa. Os dados preenchidos foram mantidos; tente novamente.';
        }
    }
}

$sucesso = $_SESSION['recompensas_sucesso'] ?? '';
unset($_SESSION['recompensas_sucesso']);

$dados = null;
$parceiros = [];
try {
    $dados = $repo->listar();
    $parceiros = $repo->parceiros();
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha ao consultar recompensas: ' . $e->getMessage());
    http_response_code(503);
    $erro = $erro ?: 'Não foi possível carregar as recompensas. Tente novamente em instantes.';
}

$esc = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$inteiro = static fn($valor): string => number_format((float) $valor, 0, ',', '.');
$data = static fn($valor): string => $valor ? date('d/m/Y H:i', strtotime((string) $valor)) : '—';
$textoPost = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
$valores = [
    'nome' => $acao === 'criar' ? $textoPost('nome') : '',
    'parceiro_id' => $acao === 'criar' ? $textoPost('parceiro_id') : '',
    'descricao' => $acao === 'criar' ? $textoPost('descricao') : '',
    'custo_pontos' => $acao === 'criar' ? $textoPost('custo_pontos') : '',
    'estoque' => $acao === 'criar' ? $textoPost('estoque') : '',
    'validade_dias' => $acao === 'criar' ? $textoPost('validade_dias') : '',
    'status' => $acao === 'criar' && $textoPost('status') === 'inativo' ? 'inativo' : 'ativo',
];

$recompensas = $dados ?? [];
$indicadores = [
    'total' => count($recompensas),
    'ativas' => count(array_filter($recompensas, static fn(array $r): bool => $r['status'] === 'ativo')),
    'pausadas' => count(array_filter($recompensas, static fn(array $r): bool => $r['status'] === 'inativo')),
    'esgotadas' => count(array_filter($recompensas, static fn(array $r): bool => $r['estoque'] !== null && (int) $r['estoque'] <= 0)),
    'resgates' => (int) array_sum(array_column($recompensas, 'resgates')),
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recompensas · EcoDrop Admin</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'admin', 'admin-recompensas', 'theme'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="layout-admin">
    <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>
    <main class="admin-conteudo recompensas-pagina">
        <header class="admin-cabecalho">
            <div>
                <p class="recompensas-sobretitulo">CATÁLOGO DE BENEFÍCIOS</p>
                <h1>Recompensas</h1>
                <p class="texto-suave">Crie os benefícios que os usuários podem resgatar com os pontos das coletas.</p>
            </div>
            <div class="flex itens-centro gap-3"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?><span><?= $esc($usuario['nome']) ?></span><?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?></div>
        </header>

        <?php if ($erro): ?><p class="recompensas-mensagem recompensas-mensagem--erro" role="alert"><?= $esc($erro) ?></p><?php endif; ?>
        <?php if ($sucesso): ?><p class="recompensas-mensagem" role="status"><?= $esc($sucesso) ?></p><?php endif; ?>

        <?php if ($dados !== null): ?>
        <section class="regua-indicadores recompensas-indicadores" aria-label="Resumo das recompensas">
            <div class="item"><p class="valor"><?= $inteiro($indicadores['total']) ?></p><p class="rotulo">Recompensas cadastradas</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['ativas']) ?></p><p class="rotulo">Ativas no app</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['pausadas']) ?></p><p class="rotulo">Pausadas</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['esgotadas']) ?></p><p class="rotulo">Esgotadas</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['resgates']) ?></p><p class="rotulo">Resgates realizados</p></div>
        </section>

        <div class="recompensas-layout">
            <section class="recompensas-catalogo" aria-labelledby="catalogo-titulo">
                <div class="recompensas-secao-cabecalho">
                    <div>
                        <h2 id="catalogo-titulo">Catálogo de recompensas</h2>
                        <p class="texto-suave">Itens disponíveis para troca por pontos.</p>
                    </div>
                    <a class="botao botao-primario" href="#nova-recompensa">Nova recompensa</a>
                </div>

                <?php if (!$recompensas): ?>
                <div class="recompensas-vazio">
                    <h3>Nenhuma recompensa cadastrada</h3>
                </div>
                <?php else: ?>
                <div class="recompensas-grade">
                    <?php foreach ($recompensas as $recompensa): ?>
                    <?php
                    $estoque = $recompensa['estoque'];
                    $esgotada = $estoque !== null && (int) $estoque <= 0;
                    $rotuloStatus = $recompensa['status'] === 'inativo' ? 'Pausada' : ($esgotada ? 'Esgotada' : 'Ativa no app');
                    $classeStatus = $recompensa['status'] === 'inativo' ? 'inativo' : ($esgotada ? 'esgotada' : 'ativo');
                    ?>
                    <article class="recompensas-card">
                        <div class="recompensas-card-topo">
                            <span class="recompensas-icone" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($recompensa['nome'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                            <div class="recompensas-card-identidade">
                                <span class="recompensas-parceiro"><?= $esc($recompensa['parceiro'] ?? 'EcoDrop') ?></span>
                                <span class="recompensas-status recompensas-status--<?= $esc($classeStatus) ?>"><?= $esc($rotuloStatus) ?></span>
                            </div>
                        </div>
                        <h3><?= $esc($recompensa['nome']) ?></h3>
                        <p class="recompensas-descricao"><?= $esc($recompensa['descricao'] ?? 'Sem descrição.') ?></p>
                        <div class="recompensas-metricas">
                            <div><strong><?= $inteiro($recompensa['custo_pontos'] ?? 0) ?></strong><span>pontos</span></div>
                            <div><strong><?= $estoque === null ? '∞' : $inteiro($estoque) ?></strong><span><?= $estoque === null ? 'ilimitado' : 'em estoque' ?></span></div>
                            <div><strong><?= $recompensa['validade_dias'] === null ? '—' : $inteiro($recompensa['validade_dias']) ?></strong><span><?= $recompensa['validade_dias'] === null ? 'sem validade' : 'dias de validade' ?></span></div>
                        </div>
                        <div class="recompensas-card-rodape">
                            <small>Criada em <?= $esc($data($recompensa['criado_em'])) ?> · <?= $inteiro($recompensa['resgates']) ?> <?= (int) $recompensa['resgates'] === 1 ? 'resgate' : 'resgates' ?></small>
                            <form method="post" action="<?= Url::escapar('/admin/recompensas/') ?>">
                                <?= Csrf::campoHtml() ?>
                                <input type="hidden" name="acao" value="status">
                                <input type="hidden" name="id" value="<?= (int) $recompensa['id'] ?>">
                                <button class="botao botao-secundario recompensas-botao-status" type="submit"><?= $recompensa['status'] === 'ativo' ? 'Pausar no app' : 'Publicar no app' ?></button>
                            </form>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <aside class="recompensas-form-painel" id="nova-recompensa" aria-labelledby="nova-titulo">
                <div class="recompensas-form-cabecalho">
                    <span class="recompensas-sobretitulo">CADASTRO</span>
                    <h2 id="nova-titulo">Nova recompensa</h2>
                </div>
                <form method="post" action="<?= Url::escapar('/admin/recompensas/') ?>" class="recompensas-form">
                    <?= Csrf::campoHtml() ?>
                    <input type="hidden" name="acao" value="criar">

                    <label class="recompensas-campo">Nome da recompensa
                        <input name="nome" type="text" maxlength="150" required value="<?= $esc($valores['nome']) ?>" placeholder="Ex.: Cupom de desconto">
                    </label>

                    <label class="recompensas-campo">Parceiro
                        <select name="parceiro_id">
                            <option value="">EcoDrop (sem parceiro)</option>
                            <?php foreach ($parceiros as $parceiro): ?>
                            <option value="<?= (int) $parceiro['id'] ?>" <?= (string) $parceiro['id'] === $valores['parceiro_id'] ? 'selected' : '' ?>><?= $esc($parceiro['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="recompensas-campo">Descrição
                        <textarea name="descricao" rows="4" maxlength="2000" required placeholder="Explique o benefício e como ele pode ser utilizado."><?= $esc($valores['descricao']) ?></textarea>
                    </label>

                    <div class="recompensas-duplo">
                        <label class="recompensas-campo">Custo em pontos
                            <input name="custo_pontos" type="number" min="1" step="1" required value="<?= $esc($valores['custo_pontos']) ?>" placeholder="450">
                        </label>
                        <label class="recompensas-campo">Estoque
                            <input name="estoque" type="number" min="0" step="1" value="<?= $esc($valores['estoque']) ?>" placeholder="Vazio = ilimitado">
                        </label>
                    </div>

                    <div class="recompensas-duplo">
                        <label class="recompensas-campo">Validade em dias
                            <input name="validade_dias" type="number" min="1" max="3650" step="1" value="<?= $esc($valores['validade_dias']) ?>" placeholder="Vazio = sem validade">
                        </label>
                        <label class="recompensas-campo">Publicação
                            <select name="status">
                                <option value="ativo" <?= $valores['status'] === 'ativo' ? 'selected' : '' ?>>Ativa no app</option>
                                <option value="inativo" <?= $valores['status'] === 'inativo' ? 'selected' : '' ?>>Pausada</option>
                            </select>
                        </label>
                    </div>

                    <p class="recompensas-ajuda">Estoque vazio significa quantidade ilimitada. A validade é aplicada ao voucher emitido no resgate.</p>
                    <button class="botao botao-primario recompensas-form-botao" type="submit">Cadastrar recompensa</button>
                </form>
            </aside>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
