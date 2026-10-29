<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminEstacoesRepository.php';

$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
$paginaAtual = 'estacoes';
header('Cache-Control: no-store');

$repo = new AdminEstacoesRepository();
$erro = '';
$acao = is_string($_POST['acao'] ?? null) ? $_POST['acao'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        $erro = 'Sua sessão do formulário expirou. Recarregue a página e tente novamente.';
    } else {
        try {
            if ($acao === 'criar') {
                $criada = $repo->criar($_POST);
                $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : 'Estação';
                $_SESSION['estacoes_sucesso'] = 'Estação “' . $nome . '” cadastrada com o código ' . $criada['codigo'] . '.';
                header('Location: ' . Url::para('/admin/estacoes/'), true, 303);
                exit;
            }

            if ($acao === 'status') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) {
                    throw new InvalidArgumentException('Estação inválida.');
                }

                $novoStatus = $repo->alternarStatus((int) $id);
                $_SESSION['estacoes_sucesso'] = $novoStatus === 'ativa'
                    ? 'Estação ativada. Ela já pode ser selecionada nas novas pesagens.'
                    : 'Estação desativada. Ela deixou de aparecer nas novas pesagens.';
                header('Location: ' . Url::para('/admin/estacoes/'), true, 303);
                exit;
            }

            throw new InvalidArgumentException('Ação inválida.');
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $erro = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[EcoDrop] Falha ao salvar estação: ' . $e->getMessage());
            http_response_code(503);
            $erro = 'Não foi possível salvar a estação. Os dados preenchidos foram mantidos; tente novamente.';
        }
    }
}

$sucesso = $_SESSION['estacoes_sucesso'] ?? '';
unset($_SESSION['estacoes_sucesso']);

$dados = null;
try {
    $dados = $repo->visaoGeral();
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha ao consultar estações: ' . $e->getMessage());
    http_response_code(503);
    $erro = $erro ?: 'Não foi possível carregar as estações. Tente novamente em instantes.';
}

$esc = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$inteiro = static fn($valor): string => number_format((float) $valor, 0, ',', '.');
$decimal = static fn($valor): string => number_format((float) $valor, 3, ',', '.');
$data = static fn($valor): string => $valor ? date('d/m/Y H:i', strtotime((string) $valor)) : 'Nenhum descarte';
$textoPost = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
$valores = [
    'nome' => $acao === 'criar' ? $textoPost('nome') : '',
    'endereco' => $acao === 'criar' ? $textoPost('endereco') : '',
    'status' => $acao === 'criar' && isset(AdminEstacoesRepository::STATUS[$textoPost('status')])
        ? $textoPost('status')
        : 'ativa',
];

$todas = $dados['estacoes'] ?? [];
$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$statusFiltro = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
if (!isset(AdminEstacoesRepository::STATUS[$statusFiltro])) {
    $statusFiltro = '';
}
$filtradas = array_filter(
    $todas,
    static fn(array $estacao): bool => ($statusFiltro === '' || $estacao['status'] === $statusFiltro)
        && ($busca === '' || mb_stripos($estacao['nome'] . ' ' . ($estacao['endereco'] ?? ''), $busca, 0, 'UTF-8') !== false)
);
$materiaisPorEstacao = [];
foreach ($dados['materiais'] ?? [] as $material) {
    $materiaisPorEstacao[(int) $material['estacao_id']][] = $material;
}
$indicadores = [
    'total' => count($todas),
    'ativas' => count(array_filter($todas, static fn(array $estacao): bool => $estacao['status'] === 'ativa')),
    'descartes' => (int) array_sum(array_column($todas, 'registros')),
    'kg' => (float) array_sum(array_column($todas, 'kg')),
    'litros' => (float) array_sum(array_column($todas, 'litros')),
];
$rotuloStatus = static fn(string $status): string => AdminEstacoesRepository::STATUS[$status] ?? 'Inativa';
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
            <div>
                <p class="estacoes-sobretitulo">GESTÃO DA REDE</p>
                <h1>Estações</h1>
                <p class="texto-suave">Cadastre os pontos de coleta e acompanhe a operação da rede.</p>
            </div>
            <div class="flex itens-centro gap-3">
                <?php require __DIR__ . '/../../components/theme/toggle.php'; ?>
                <span><?= $esc($usuario['nome']) ?></span>
                <?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?>
            </div>
        </header>

        <?php if ($erro): ?><p class="estacoes-mensagem estacoes-mensagem--erro" role="alert"><?= $esc($erro) ?></p><?php endif; ?>
        <?php if ($sucesso): ?><p class="estacoes-mensagem" role="status"><?= $esc($sucesso) ?></p><?php endif; ?>

        <?php if ($dados === null): ?>
            <div class="estacoes-vazio" role="alert">
                <h2>Dados indisponíveis</h2>
                <p>Não foi possível consultar as estações. Recarregue a página para tentar novamente.</p>
            </div>
        <?php else: ?>
            <section class="regua-indicadores estacoes-indicadores" aria-label="Resumo de todas as estações">
                <div class="item"><p class="valor"><?= $inteiro($indicadores['total']) ?></p><p class="rotulo">Estações cadastradas</p></div>
                <div class="item"><p class="valor"><?= $inteiro($indicadores['ativas']) ?></p><p class="rotulo">Estações ativas</p></div>
                <div class="item"><p class="valor"><?= $inteiro($indicadores['descartes']) ?></p><p class="rotulo">Descartes registrados</p></div>
                <div class="item"><p class="valor"><?= $decimal($indicadores['kg']) ?></p><p class="rotulo">Sólidos recebidos · kg</p></div>
                <div class="item"><p class="valor"><?= $decimal($indicadores['litros']) ?></p><p class="rotulo">Líquidos recebidos · L</p></div>
            </section>

            <p class="estacoes-nota"><strong>Visão geral da operação:</strong> os códigos públicos são gerados no cadastro. Estações inativas deixam de aparecer nas novas pesagens, sem apagar o histórico.</p>

            <div class="estacoes-layout">
                <section class="estacoes-lista" aria-labelledby="rede-titulo">
                    <div class="estacoes-secao-cabecalho">
                        <div>
                            <h2 id="rede-titulo">Estações cadastradas</h2>
                            <p class="texto-suave">Códigos, status, endereço e volumes recebidos em cada ponto.</p>
                        </div>
                    </div>

                    <details class="estacoes-cadastro" id="nova-estacao" <?= $acao === 'criar' && $erro !== '' ? 'open' : '' ?>>
                        <summary class="botao botao-primario">Nova estação</summary>
                        <div class="estacoes-form-painel">
                            <form method="post" action="<?= Url::escapar('/admin/estacoes/') ?>" class="estacoes-form">
                                <?= Csrf::campoHtml() ?>
                                <input type="hidden" name="acao" value="criar">
                                <label class="estacoes-campo">Nome da estação
                                    <input name="nome" type="text" maxlength="150" required value="<?= $esc($valores['nome']) ?>" placeholder="Ex.: EcoDrop Praça da Sé">
                                </label>
                                <label class="estacoes-campo">Endereço
                                    <input name="endereco" type="text" maxlength="255" value="<?= $esc($valores['endereco']) ?>" placeholder="Rua, número e referência">
                                </label>
                                <label class="estacoes-campo">Disponibilidade
                                    <select name="status">
                                        <?php foreach (AdminEstacoesRepository::STATUS as $chave => $rotulo): ?>
                                        <option value="<?= $esc($chave) ?>" <?= $valores['status'] === $chave ? 'selected' : '' ?>><?= $esc($rotulo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <button class="botao botao-primario estacoes-form-botao" type="submit">Cadastrar estação</button>
                            </form>
                        </div>
                    </details>

                    <form class="estacoes-filtros" method="get" action="<?= Url::escapar('/admin/estacoes/') ?>">
                        <label>Buscar estação
                            <input type="search" name="busca" value="<?= $esc($busca) ?>" placeholder="Nome ou endereço">
                        </label>
                        <label>Status
                            <select name="status">
                                <option value="">Todos os status</option>
                                <?php foreach (AdminEstacoesRepository::STATUS as $chave => $rotulo): ?>
                                <option value="<?= $esc($chave) ?>" <?= $statusFiltro === $chave ? 'selected' : '' ?>><?= $esc($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="botao botao-primario" type="submit">Filtrar estações</button>
                        <a class="botao botao-secundario" href="<?= Url::escapar('/admin/estacoes/') ?>">Limpar</a>
                    </form>

                    <p class="estacoes-contagem"><?= count($filtradas) ?> de <?= count($todas) ?> estações</p>

                    <?php if (!$filtradas): ?>
                        <div class="estacoes-vazio">
                            <h3><?= $todas ? 'Nenhuma estação encontrada' : 'Nenhuma estação cadastrada' ?></h3>
                        </div>
                    <?php else: ?>
                        <div class="estacoes-grade">
                            <?php foreach ($filtradas as $estacao): ?>
                                <?php
                                $statusEstacao = isset(AdminEstacoesRepository::STATUS[$estacao['status']]) ? $estacao['status'] : 'inativa';
                                $materiaisEstacao = $materiaisPorEstacao[(int) $estacao['id']] ?? [];
                                $invalidos = (int) $estacao['registros'] - (int) $estacao['validos'];
                                ?>
                                <article class="estacoes-card">
                                    <div class="estacoes-card-topo">
                                        <span class="estacoes-codigo"><?= $esc($estacao['codigo']) ?></span>
                                        <span class="estacoes-status estacoes-status--<?= $esc($statusEstacao) ?>"><?= $esc($rotuloStatus($statusEstacao)) ?></span>
                                    </div>
                                    <h3><?= $esc($estacao['nome']) ?></h3>
                                    <p class="estacoes-endereco"><?= $esc($estacao['endereco'] ?: 'Endereço não informado') ?></p>

                                    <div class="estacoes-metricas">
                                        <div><strong><?= $inteiro($estacao['registros']) ?></strong><span>descartes</span></div>
                                        <div><strong><?= $decimal($estacao['kg']) ?> <small>kg</small></strong><span>sólidos</span></div>
                                        <div><strong><?= $decimal($estacao['litros']) ?> <small>L</small></strong><span>líquidos</span></div>
                                        <div><strong><?= $inteiro($estacao['pontos']) ?></strong><span>pontos gerados</span></div>
                                    </div>

                                    <details class="estacoes-detalhes">
                                        <summary>Materiais recebidos nesta estação</summary>
                                        <?php if (!$materiaisEstacao): ?>
                                            <p class="texto-suave">Esta estação ainda não possui descartes válidos.</p>
                                        <?php else: ?>
                                            <ul class="estacoes-materiais">
                                                <?php foreach ($materiaisEstacao as $material): ?>
                                                <li>
                                                    <span><?= $esc($material['nome']) ?><small><?= $inteiro($material['registros']) ?> <?= (int) $material['registros'] === 1 ? 'descarte válido' : 'descartes válidos' ?></small></span>
                                                    <strong><?= $decimal($material['quantidade']) ?> <?= $esc($material['unidade']) ?></strong>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </details>

                                    <footer class="estacoes-card-rodape">
                                        <span>
                                            <?= $inteiro($estacao['validos']) ?> <?= (int) $estacao['validos'] === 1 ? 'válido' : 'válidos' ?>
                                            · <?= $inteiro($invalidos) ?> <?= $invalidos === 1 ? 'inválido' : 'inválidos' ?><br>
                                            Último descarte: <?= $esc($data($estacao['ultimo'])) ?>
                                        </span>
                                        <form method="post" action="<?= Url::escapar('/admin/estacoes/') ?>">
                                            <?= Csrf::campoHtml() ?>
                                            <input type="hidden" name="acao" value="status">
                                            <input type="hidden" name="id" value="<?= (int) $estacao['id'] ?>">
                                            <button class="botao botao-secundario estacoes-botao-status" type="submit"><?= $statusEstacao === 'ativa' ? 'Desativar estação' : 'Ativar estação' ?></button>
                                        </form>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

            </div>

            <section class="estacoes-historico" aria-labelledby="historico-titulo">
                <h2 id="historico-titulo">Últimos descartes da rede</h2>
                <p class="texto-suave">Os 12 registros mais recentes de todas as estações, incluindo descartes inválidos.</p>
                <div class="tabela-wrapper" role="region" aria-label="Descartes recentes, tabela com rolagem horizontal" tabindex="0">
                    <table class="tabela-ecodrop">
                        <thead><tr><th>Data</th><th>Estação</th><th>Material</th><th>Quantidade</th><th>Resultado</th></tr></thead>
                        <tbody>
                            <?php foreach ($dados['recentes'] as $descarte): ?>
                            <tr>
                                <td><?= $esc($data($descarte['criado_em'])) ?></td>
                                <td><?= $esc($descarte['estacao']) ?></td>
                                <td><?= $esc($descarte['material']) ?></td>
                                <td><?= $decimal($descarte['quantidade']) ?> <?= $esc($descarte['unidade']) ?></td>
                                <td><?= $descarte['status'] === 'valido' ? 'Válido' : 'Inválido' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$dados['recentes']): ?><tr><td colspan="5">Nenhum descarte registrado até o momento.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
