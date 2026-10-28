<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminMateriaisRepository.php';

$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
$paginaAtual = 'materiais';
header('Cache-Control: no-store');

$periodo = is_string($_GET['periodo'] ?? null) ? $_GET['periodo'] : 'geral';
if (!isset(AdminMateriaisRepository::PERIODOS[$periodo])) {
    $periodo = 'geral';
}

$dados = null;
try {
    $dados = (new AdminMateriaisRepository())->visaoGeral($periodo);
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha ao consultar materiais: ' . $e->getMessage());
    http_response_code(503);
}

$esc = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$inteiro = static fn($valor): string => number_format((float) $valor, 0, ',', '.');
$decimal = static fn($valor): string => number_format((float) $valor, 3, ',', '.');
$percentual = static fn($valor): string => number_format((float) $valor, 1, ',', '.') . '%';
$dataHora = static fn($valor): string => $valor ? date('d/m/Y H:i', strtotime((string) $valor)) : '—';
$volume = static function (float $kg, float $litros) use ($decimal): string {
    $partes = [];
    if ($kg > 0) {
        $partes[] = $decimal($kg) . ' kg';
    }
    if ($litros > 0) {
        $partes[] = $decimal($litros) . ' L';
    }
    return $partes ? implode(' · ', $partes) : '—';
};
$rotulosStatus = ['ativo' => 'Ativo', 'inativo' => 'Inativo'];
$periodoDescricao = $periodo === 'mes' ? 'no mês atual' : 'em todo o histórico';
$periodoResumo = $periodo === 'mes' ? 'do mês atual' : 'geral';

$materiais = $dados['materiais'] ?? [];
$categorias = $dados['categorias'] ?? [];
$indicadores = $dados['indicadores'] ?? [];
$depositados = array_values(array_filter(
    $materiais,
    static fn(array $material): bool => $material['depositos'] > 0
));
$topMateriais = array_slice($depositados, 0, 6);
$maiorDeposito = max(1, ...array_map(
    static fn(array $material): int => $material['depositos'],
    $topMateriais ?: [['depositos' => 0]]
));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Materiais · EcoDrop Admin</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'admin', 'admin-materiais', 'theme'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="layout-admin">
    <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>
    <main class="admin-conteudo materiais-pagina">
        <header class="admin-cabecalho">
            <div>
                <p class="materiais-sobretitulo">GESTÃO DE RESÍDUOS</p>
                <h1>Materiais</h1>
                <p class="texto-suave">O que a comunidade está depositando e como os resíduos se separam.</p>
            </div>
            <div class="flex itens-centro gap-3"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?><span><?= $esc($usuario['nome']) ?></span><?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?></div>
        </header>

        <div class="materiais-controles">
            <div class="materiais-periodos" role="group" aria-label="Período dos materiais">
                <?php foreach (AdminMateriaisRepository::PERIODOS as $chave => $rotulo): ?>
                <a class="materiais-periodo<?= $chave === $periodo ? ' materiais-periodo--ativo' : '' ?>" href="<?= Url::escapar('/admin/materiais/?periodo=' . $chave) ?>" <?= $chave === $periodo ? 'aria-current="page"' : '' ?>><?= $esc($rotulo) ?></a>
                <?php endforeach; ?>
            </div>
            <p class="materiais-atualizado"><?= $dados === null ? 'Dados indisponíveis' : 'Dados do banco · atualizado em ' . $esc($dados['atualizado']) . ' (Brasília)' ?></p>
        </div>

        <?php if ($dados === null): ?>
        <div class="materiais-vazio" role="alert">
            <h2>Não foi possível carregar os materiais</h2>
            <p>Houve uma falha na consulta ao banco de dados. Recarregue a página para tentar novamente.</p>
        </div>
        <?php elseif (!$materiais): ?>
        <div class="materiais-vazio">
            <h2>Nenhum tipo de material cadastrado</h2>
            <p>Cadastre os materiais aceitos pela rede para acompanhar os depósitos e a separação por categoria.</p>
        </div>
        <?php else: ?>
        <section class="regua-indicadores materiais-indicadores" aria-label="Resumo dos materiais <?= $esc($periodoResumo) ?>">
            <div class="item"><p class="valor"><?= $inteiro($indicadores['tipos_cadastrados']) ?></p><p class="rotulo">Tipos cadastrados</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['tipos_coletados']) ?></p><p class="rotulo">Tipos coletados</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['depositos']) ?></p><p class="rotulo">Depósitos válidos</p></div>
            <div class="item"><p class="valor"><?= $decimal($indicadores['kg']) ?></p><p class="rotulo">Sólidos recolhidos · kg</p></div>
            <div class="item"><p class="valor"><?= $decimal($indicadores['litros']) ?></p><p class="rotulo">Líquidos recolhidos · L</p></div>
        </section>

        <section class="materiais-secao" aria-labelledby="categorias-titulo">
            <div class="materiais-secao-cabecalho">
                <div>
                    <p class="materiais-sobretitulo">SEPARAÇÃO POR TIPO</p>
                    <h2 id="categorias-titulo">Categorias de resíduos</h2>
                    <p class="texto-suave">Participação de cada categoria nos depósitos válidos <?= $esc($periodoDescricao) ?>.</p>
                </div>
                <span class="materiais-pill"><?= count($categorias) ?> <?= count($categorias) === 1 ? 'categoria cadastrada' : 'categorias cadastradas' ?></span>
            </div>
            <div class="materiais-categorias-grade">
                <?php foreach ($categorias as $categoria): ?>
                <article class="materiais-categoria materiais-categoria--<?= $esc($categoria['chave']) ?>">
                    <div class="materiais-categoria-topo">
                        <span class="materiais-categoria-icone" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($categoria['nome'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                        <div>
                            <h3><?= $esc($categoria['nome']) ?></h3>
                            <p><?= (int) $categoria['materiais'] ?> <?= (int) $categoria['materiais'] === 1 ? 'material cadastrado' : 'materiais cadastrados' ?></p>
                        </div>
                    </div>
                    <div class="materiais-categoria-numeros">
                        <strong><?= $inteiro($categoria['depositos']) ?></strong>
                        <span><?= (int) $categoria['depositos'] === 1 ? 'depósito' : 'depósitos' ?></span>
                        <em><?= $percentual($categoria['percentual']) ?></em>
                    </div>
                    <p class="materiais-categoria-volume"><?= $esc($volume($categoria['kg'], $categoria['litros'])) ?> · <?= $inteiro($categoria['pontos']) ?> pts</p>
                    <div class="materiais-barra" aria-hidden="true"><span style="width: <?= (float) $categoria['percentual'] ?>%"></span></div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="materiais-grade">
            <section class="admin-painel materiais-painel" aria-labelledby="ranking-titulo">
                <div class="materiais-secao-cabecalho">
                    <div>
                        <h2 id="ranking-titulo">Materiais mais depositados</h2>
                        <p class="texto-suave">Ordenados pelo número de depósitos válidos <?= $esc($periodoDescricao) ?>.</p>
                    </div>
                    <?php if ($dados['destaque'] !== null): ?>
                    <span class="materiais-pill">Destaque: <?= $esc($dados['destaque']['nome']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!$topMateriais): ?>
                <p class="materiais-vazio-texto">Nenhum depósito válido registrado neste período.</p>
                <?php else: ?>
                <ol class="materiais-ranking">
                    <?php foreach ($topMateriais as $indice => $material): ?>
                    <li class="materiais-ranking-item materiais-categoria--<?= $esc($material['categoria']) ?>">
                        <span class="materiais-ranking-posicao"><?= $indice + 1 ?></span>
                        <div class="materiais-ranking-conteudo">
                            <div class="materiais-ranking-topo">
                                <strong><?= $esc($material['nome']) ?></strong>
                                <span class="materiais-tag materiais-categoria--<?= $esc($material['categoria']) ?>"><?= $esc($material['categoria_nome']) ?></span>
                            </div>
                            <div class="materiais-barra" aria-hidden="true"><span style="width: <?= round(($material['depositos'] / $maiorDeposito) * 100, 1) ?>%"></span></div>
                            <p>
                                <small><?= $inteiro($material['depositos']) ?> <?= $material['depositos'] === 1 ? 'depósito' : 'depósitos' ?> · <?= $decimal($material['quantidade']) ?> <?= $esc($material['unidade']) ?> · <?= $percentual($material['percentual']) ?> do total</small>
                                <em><?= $inteiro($material['pontos']) ?> pts</em>
                            </p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </section>

            <section class="admin-painel materiais-painel" aria-labelledby="recentes-titulo">
                <div class="materiais-secao-cabecalho">
                    <div>
                        <h2 id="recentes-titulo">Depósitos recentes</h2>
                        <p class="texto-suave">Últimas coletas válidas registradas.</p>
                    </div>
                </div>
                <?php if (!$dados['recentes']): ?>
                <p class="materiais-vazio-texto">Nenhum depósito registrado neste período.</p>
                <?php else: ?>
                <ul class="materiais-recentes">
                    <?php foreach ($dados['recentes'] as $recente): ?>
                    <?php $categoriaRecente = AdminMateriaisRepository::categoriaDe((string) $recente['material'], ''); ?>
                    <li class="materiais-categoria--<?= $esc($categoriaRecente) ?>">
                        <span class="materiais-recentes-icone" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr((string) $recente['material'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                        <span class="materiais-recentes-dados">
                            <strong><?= $esc($recente['material']) ?></strong>
                            <small><?= $esc($recente['usuario']) ?> · <?= $esc($recente['estacao']) ?></small>
                            <small><?= $esc($dataHora($recente['criado_em'])) ?></small>
                        </span>
                        <em><?= $decimal($recente['quantidade']) ?> <?= $esc($recente['unidade']) ?><small>+<?= $inteiro($recente['pontos_gerados']) ?> pts</small></em>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </section>
        </div>

        <section class="admin-painel materiais-painel materiais-lista" aria-labelledby="lista-titulo">
            <div class="materiais-secao-cabecalho">
                <div>
                    <h2 id="lista-titulo">Todos os materiais</h2>
                    <p class="texto-suave">Contagem e volume dos depósitos válidos, mais a configuração de pontuação de cada tipo.</p>
                </div>
                <span class="materiais-pill"><?= $inteiro($indicadores['pontos']) ?> pontos gerados</span>
            </div>
            <div class="tabela-wrapper" role="region" aria-label="Materiais e depósitos, tabela com rolagem horizontal" tabindex="0">
                <table class="tabela-ecodrop materiais-tabela">
                    <thead>
                        <tr>
                            <th scope="col">Material</th>
                            <th scope="col">Categoria</th>
                            <th scope="col">Depósitos</th>
                            <th scope="col">Quantidade</th>
                            <th scope="col">Pontos gerados</th>
                            <th scope="col">Pontuação/unidade</th>
                            <th scope="col">Participação</th>
                            <th scope="col">Último depósito</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiais as $material): ?>
                        <tr>
                            <td>
                                <div class="materiais-nome">
                                    <span class="materiais-avatar materiais-categoria--<?= $esc($material['categoria']) ?>" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($material['nome'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                                    <span><strong><?= $esc($material['nome']) ?></strong><small>Mínimo <?= $decimal($material['quantidade_minima']) ?> <?= $esc($material['unidade']) ?></small></span>
                                </div>
                            </td>
                            <td><span class="materiais-tag materiais-categoria--<?= $esc($material['categoria']) ?>"><?= $esc($material['categoria_nome']) ?></span></td>
                            <td><?= $inteiro($material['depositos']) ?></td>
                            <td><?= $decimal($material['quantidade']) ?> <?= $esc($material['unidade']) ?></td>
                            <td><strong class="materiais-pontos"><?= $inteiro($material['pontos']) ?></strong></td>
                            <td><?= $material['pontos_por_unidade'] === null ? 'Não configurada' : $inteiro($material['pontos_por_unidade']) . ' pts/' . $esc($material['unidade']) ?></td>
                            <td>
                                <div class="materiais-participacao">
                                    <div class="materiais-barra" aria-hidden="true"><span style="width: <?= (float) $material['percentual'] ?>%"></span></div>
                                    <small><?= $percentual($material['percentual']) ?></small>
                                </div>
                            </td>
                            <td><?= $esc($dataHora($material['ultimo'])) ?></td>
                            <td><span class="materiais-status materiais-status--<?= $esc($material['status']) ?>"><?= $esc($rotulosStatus[$material['status']] ?? ucfirst($material['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="materiais-legenda">Somente descartes válidos entram nos totais de depósitos, volume e pontos. Descartes inválidos continuam disponíveis no histórico da estação, mas não pontuam nem entram no ranking de materiais.</p>
        </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
