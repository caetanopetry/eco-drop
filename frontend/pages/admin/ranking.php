<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminRankingRepository.php';

$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
$paginaAtual = 'ranking';
header('Cache-Control: no-store');

$periodo = is_string($_GET['periodo'] ?? null) ? $_GET['periodo'] : 'geral';
if (!isset(AdminRankingRepository::PERIODOS[$periodo])) {
    $periodo = 'geral';
}

$dados = null;
try {
    $dados = (new AdminRankingRepository())->visaoGeral($periodo);
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha ao consultar o ranking: ' . $e->getMessage());
    http_response_code(503);
}

$esc = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$inteiro = static fn($valor): string => number_format((float) $valor, 0, ',', '.');
$decimal = static fn($valor): string => number_format((float) $valor, 3, ',', '.');
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
$rotulosStatus = ['ativo' => 'Ativo', 'inativo' => 'Inativo', 'bloqueado' => 'Bloqueado'];

$ranking = $dados['ranking'] ?? [];
$podio = array_slice($ranking, 0, 3);
$indicadores = $dados['indicadores'] ?? [];
$periodoDescricao = $periodo === 'mes' ? 'no mês atual' : 'no ranking geral';
$periodoResumo = $periodo === 'mes' ? 'do mês atual' : 'geral';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ranking · EcoDrop Admin</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'admin', 'admin-ranking', 'theme'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="layout-admin">
    <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>
    <main class="admin-conteudo ranking-pagina">
        <header class="admin-cabecalho">
            <div>
                <p class="ranking-sobretitulo">RECONHECIMENTO</p>
                <h1>Ranking</h1>
                <p class="texto-suave">Quem mais pontua coletando materiais recicláveis no EcoDrop.</p>
            </div>
            <div class="flex itens-centro gap-3"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?><span><?= $esc($usuario['nome']) ?></span><?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?></div>
        </header>

        <div class="ranking-controles">
            <div class="ranking-periodos" role="group" aria-label="Período do ranking">
                <?php foreach (AdminRankingRepository::PERIODOS as $chave => $rotulo): ?>
                <a class="ranking-periodo<?= $chave === $periodo ? ' ranking-periodo--ativo' : '' ?>" href="<?= Url::escapar('/admin/ranking/?periodo=' . $chave) ?>" <?= $chave === $periodo ? 'aria-current="page"' : '' ?>><?= $esc($rotulo) ?></a>
                <?php endforeach; ?>
            </div>
            <p class="ranking-atualizado"><?= $dados === null ? 'Dados indisponíveis' : 'Dados do banco · atualizado em ' . $esc($dados['atualizado']) . ' (Brasília)' ?></p>
        </div>

        <?php if ($dados === null): ?>
        <div class="ranking-vazio" role="alert">
            <h2>Não foi possível carregar o ranking</h2>
            <p>Houve uma falha na consulta ao banco de dados. Recarregue a página para tentar novamente.</p>
        </div>
        <?php elseif (!$ranking): ?>
        <div class="ranking-vazio">
            <h2>Ainda não há coletores pontuando</h2>
        </div>
        <?php else: ?>
        <section class="regua-indicadores ranking-indicadores" aria-label="Resumo do ranking <?= $esc($periodoResumo) ?>">
            <div class="item"><p class="valor"><?= $inteiro($indicadores['colecionadores']) ?></p><p class="rotulo">Colecionadores</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['pontos']) ?></p><p class="rotulo">Pontos em coletas</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['descartes']) ?></p><p class="rotulo">Coletas válidas</p></div>
            <div class="item"><p class="valor"><?= $decimal($indicadores['kg']) ?></p><p class="rotulo">Sólidos coletados · kg</p></div>
            <div class="item"><p class="valor"><?= $decimal($indicadores['litros']) ?></p><p class="rotulo">Líquidos coletados · L</p></div>
        </section>

        <section class="ranking-podio" aria-labelledby="podio-titulo">
            <div class="ranking-secao-cabecalho">
                <div>
                    <p class="ranking-sobretitulo">DESTAQUES DO PERÍODO</p>
                    <h2 id="podio-titulo">Pódio de coletores</h2>
                    <p class="texto-suave">Os três maiores pontuadores <?= $esc($periodoDescricao) ?>.</p>
                </div>
                <span class="ranking-media">Média de <?= $inteiro($indicadores['media']) ?> pts por coletor</span>
            </div>
            <div class="ranking-podio-grade<?= count($podio) < 3 ? ' ranking-podio-grade--simples' : '' ?>">
                <?php foreach ($podio as $colocado): ?>
                <article class="ranking-podio-card ranking-podio-card--pos<?= (int) $colocado['posicao'] ?>">
                    <span class="ranking-podio-posicao"><?= (int) $colocado['posicao'] ?>º lugar</span>
                    <span class="ranking-avatar" aria-hidden="true"><?= $esc($colocado['inicial']) ?></span>
                    <h3><?= $esc($colocado['nome']) ?></h3>
                    <p class="ranking-podio-email"><?= $esc($colocado['email']) ?></p>
                    <strong class="ranking-podio-pontos"><?= $inteiro($colocado['pontos']) ?> <small>pts</small></strong>
                    <p class="ranking-podio-meta"><?= $inteiro($colocado['descartes']) ?> <?= $colocado['descartes'] === 1 ? 'coleta' : 'coletas' ?> · <?= $esc($volume($colocado['kg'], $colocado['litros'])) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="ranking-grade">
            <section class="admin-painel ranking-painel ranking-lista" aria-labelledby="lista-titulo">
                <div class="ranking-secao-cabecalho">
                    <div>
                        <h2 id="lista-titulo">Classificação completa</h2>
                        <p class="texto-suave">Pontos acumulados exclusivamente por coletas registradas nas estações.</p>
                    </div>
                    <span class="ranking-contagem"><?= count($ranking) ?> <?= count($ranking) === 1 ? 'coletor' : 'coletores' ?></span>
                </div>
                <div class="tabela-wrapper" role="region" aria-label="Ranking de coletores, tabela com rolagem horizontal" tabindex="0">
                    <table class="tabela-ecodrop ranking-tabela">
                        <thead>
                            <tr>
                                <th scope="col">Posição</th>
                                <th scope="col">Coletor</th>
                                <th scope="col">Pontos</th>
                                <th scope="col">Coletas</th>
                                <th scope="col">Volume coletado</th>
                                <th scope="col">Última coleta</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $colocado): ?>
                            <tr class="<?= $colocado['posicao'] <= 3 ? 'ranking-linha--destaque' : '' ?>">
                                <td><span class="ranking-posicao ranking-posicao--<?= (int) $colocado['posicao'] ?>"><?= (int) $colocado['posicao'] ?>º</span></td>
                                <td>
                                    <div class="ranking-coletor">
                                        <span class="ranking-avatar ranking-avatar--pequeno" aria-hidden="true"><?= $esc($colocado['inicial']) ?></span>
                                        <span><strong><?= $esc($colocado['nome']) ?></strong><small><?= $esc($colocado['email']) ?></small></span>
                                    </div>
                                </td>
                                <td><strong class="ranking-pontos"><?= $inteiro($colocado['pontos']) ?></strong></td>
                                <td><?= $inteiro($colocado['descartes']) ?></td>
                                <td><?= $esc($volume($colocado['kg'], $colocado['litros'])) ?></td>
                                <td><?= $esc($dataHora($colocado['ultimo_descarte'])) ?></td>
                                <td><span class="ranking-status ranking-status--<?= $esc($colocado['status']) ?>"><?= $esc($rotulosStatus[$colocado['status']] ?? ucfirst($colocado['status'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="ranking-legenda">O ranking soma créditos de descartes e do bônus de primeiro descarte. Resgates, estornos e descartes inválidos não alteram a pontuação.</p>
            </section>

            <aside class="ranking-lateral">
                <section class="admin-painel ranking-painel" aria-labelledby="materiais-titulo">
                    <h2 id="materiais-titulo">Materiais que mais pontuam</h2>
                    <p class="texto-suave">Participação de cada material nas coletas válidas do período.</p>
                    <?php if (!$dados['materiais']): ?>
                    <p class="ranking-vazio-texto">Nenhum material coletado neste período.</p>
                    <?php else: ?>
                    <ul class="ranking-materiais">
                        <?php foreach ($dados['materiais'] as $material): ?>
                        <li>
                            <span>
                                <strong><?= $esc($material['nome']) ?></strong>
                                <small><?= $inteiro($material['descartes']) ?> <?= (int) $material['descartes'] === 1 ? 'coleta' : 'coletas' ?> · <?= $decimal($material['quantidade']) ?> <?= $esc($material['unidade']) ?></small>
                            </span>
                            <span class="ranking-material-pontos"><?= $inteiro($material['pontos']) ?> <small>pts</small></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>

                <section class="admin-painel ranking-painel" aria-labelledby="recentes-titulo">
                    <h2 id="recentes-titulo">Coletas recentes</h2>
                    <p class="texto-suave">Últimos descartes válidos que geraram pontos.</p>
                    <?php if (!$dados['recentes']): ?>
                    <p class="ranking-vazio-texto">Nenhuma coleta registrada neste período.</p>
                    <?php else: ?>
                    <ul class="ranking-recentes">
                        <?php foreach ($dados['recentes'] as $recente): ?>
                        <li>
                            <span class="ranking-avatar ranking-avatar--pequeno" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr(trim((string) $recente['usuario']), 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                            <span class="ranking-recente-dados">
                                <strong><?= $esc($recente['usuario']) ?></strong>
                                <small><?= $esc($recente['material']) ?> · <?= $decimal($recente['quantidade']) ?> <?= $esc($recente['unidade']) ?> · <?= $esc($recente['estacao']) ?></small>
                                <small><?= $esc($dataHora($recente['criado_em'])) ?></small>
                            </span>
                            <em>+<?= $inteiro($recente['pontos_gerados']) ?> pts</em>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>
            </aside>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
