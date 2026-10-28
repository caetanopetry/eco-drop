<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/MeusDescartesRepository.php';

Auth::iniciarSessao();
$usuario = Auth::exigirAutenticacaoPagina(Url::para('/app/login/'));

if ($usuario['papel'] === 'admin') {
    header('Location: ' . Url::para('/admin/dashboard/'));
    exit;
}

$usuarioId = (int) $usuario['id'];
$repo = new MeusDescartesRepository();

$mensagem = null;
$tipoMensagem = 'sucesso';
$lixeiraSelecionada = 0;
$materialSelecionado = 0;
$quantidadeInformada = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenCsrf = $_POST['csrf_token'] ?? null;
    if (!is_string($tokenCsrf) || !Csrf::validar($tokenCsrf)) {
        http_response_code(403);
        $mensagem = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $tipoMensagem = 'erro';
    } elseif (($_POST['acao'] ?? '') === 'registrar') {
        $lixeiraSelecionada = (int) ($_POST['estacao_id'] ?? 0);
        $materialSelecionado = (int) ($_POST['material_id'] ?? 0);
        $quantidadeInformada = is_string($_POST['quantidade'] ?? null) ? trim($_POST['quantidade']) : '';
        $quantidade = (float) str_replace(',', '.', $quantidadeInformada);

        if (!is_finite($quantidade) || $quantidade <= 0) {
            $mensagem = 'Informe uma quantidade válida maior que zero.';
            $tipoMensagem = 'erro';
        } else {
            $resultado = $repo->registrarPesagem(
                $usuarioId,
                $lixeiraSelecionada,
                $materialSelecionado,
                $quantidade
            );
            if ($resultado['sucesso']) {
                $_SESSION['descartes_flash'] = [
                    'tipo' => 'sucesso',
                    'mensagem' => $resultado['mensagem'] . ' Sua pesagem já aparece no histórico.',
                ];
                header('Location: ' . Url::para('/app/descartes/'), true, 303);
                exit;
            }

            $mensagem = $resultado['mensagem'];
            $tipoMensagem = 'erro';
        }
    } else {
        $mensagem = 'Ação inválida.';
        $tipoMensagem = 'erro';
    }
}

$flash = $_SESSION['descartes_flash'] ?? null;
unset($_SESSION['descartes_flash']);
if (is_array($flash)) {
    $mensagem = (string) ($flash['mensagem'] ?? '');
    $tipoMensagem = ($flash['tipo'] ?? '') === 'erro' ? 'erro' : 'sucesso';
}

$resumo = $repo->resumo($usuarioId);
$historico = $repo->historico($usuarioId);
$tipos = $repo->tiposRecolhidos($usuarioId);
$materiais = $repo->materiaisAtivos();
$lixeiras = $repo->lixeirasAtivas();

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
    return $partes ? implode(' · ', $partes) : '0 kg';
};
$paginaAtualApp = 'descartes';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus Descartes - EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/app.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/app-descartes.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/app-descartes.css') ?>">
    <script src="<?= Url::escapar('/frontend/assets/js/app-descartes.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/app-descartes.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/../../components/app/sidebar.php'; ?>

        <main class="conteudo-app descartes-pagina">
            <div class="app-topbar">
                <div>
                    <h1>Meus Descartes</h1>
                    <p>Acompanhe suas pesagens e registre um novo descarte selecionando a lixeira utilizada.</p>
                </div>
                <?php include __DIR__ . '/../../components/app/perfil.php'; ?>
            </div>

            <?php if ($mensagem !== null): ?>
                <div class="alerta alerta-<?= $esc($tipoMensagem) ?>" role="<?= $tipoMensagem === 'erro' ? 'alert' : 'status' ?>">
                    <?= $esc($mensagem) ?>
                </div>
            <?php endif; ?>

            <section class="descartes-indicadores" aria-label="Resumo das pesagens">
                <article class="cartao-resumo">
                    <span>Pesagens registradas</span>
                    <strong><?= $inteiro($resumo['pesagens']) ?></strong>
                    <em><?= $resumo['pesagens'] === 1 ? 'descarte registrado' : 'descartes registrados' ?></em>
                </article>
                <article class="cartao-resumo">
                    <span>Tipos de itens</span>
                    <strong><?= $inteiro($resumo['tipos']) ?></strong>
                    <em>materiais diferentes</em>
                </article>
                <article class="cartao-resumo">
                    <span>Volume recolhido</span>
                    <strong class="descartes-volume"><?= $esc($volume($resumo['kg'], $resumo['litros'])) ?></strong>
                    <em>somando todas as pesagens</em>
                </article>
                <article class="cartao-resumo">
                    <span>Pontos gerados</span>
                    <strong><?= $inteiro($resumo['pontos']) ?></strong>
                    <em>nas pesagens válidas</em>
                </article>
            </section>

            <div class="descartes-layout">
                <div class="descartes-coluna">
                    <section class="painel-app descartes-painel" aria-labelledby="tipos-titulo">
                        <div class="cabecalho-painel">
                            <h2 id="tipos-titulo">Tipos de itens recolhidos</h2>
                            <span><?= count($tipos) ?> <?= count($tipos) === 1 ? 'tipo' : 'tipos' ?></span>
                        </div>
                        <?php if (!$tipos): ?>
                            <p class="estado-vazio">Nenhum material recolhido ainda. Selecione uma lixeira para fazer sua primeira pesagem.</p>
                        <?php else: ?>
                            <div class="descartes-tipos">
                                <?php foreach ($tipos as $tipo): ?>
                                    <article class="descartes-tipo">
                                        <span class="descartes-tipo-icone" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($tipo['nome'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                                        <div>
                                            <strong><?= $esc($tipo['nome']) ?></strong>
                                            <small><?= $inteiro($tipo['pesagens']) ?> <?= (int) $tipo['pesagens'] === 1 ? 'pesagem' : 'pesagens' ?> · <?= $esc($decimal($tipo['quantidade'])) ?> <?= $esc($tipo['unidade']) ?></small>
                                        </div>
                                        <em><?= $inteiro($tipo['pontos']) ?> pts</em>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="painel-app descartes-painel" aria-labelledby="historico-titulo">
                        <div class="cabecalho-painel">
                            <h2 id="historico-titulo">Histórico de pesagens</h2>
                            <span><?= $inteiro($resumo['pesagens']) ?> registros</span>
                        </div>
                        <?php if (!$historico): ?>
                            <p class="estado-vazio">Suas pesagens aparecerão aqui após o primeiro descarte registrado em uma lixeira EcoDrop.</p>
                        <?php else: ?>
                            <ol class="descartes-historico">
                                <?php foreach ($historico as $descarte): ?>
                                    <?php $valida = $descarte['status'] === 'valido'; ?>
                                    <li>
                                        <span class="descartes-historico-icone" aria-hidden="true"><?= $esc(mb_strtoupper(mb_substr($descarte['material'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                                        <div class="descartes-historico-dados">
                                            <div class="descartes-historico-topo">
                                                <strong><?= $esc($descarte['material']) ?></strong>
                                                <span class="descartes-status <?= $valida ? 'descartes-status--valida' : 'descartes-status--aviso' ?>"><?= $valida ? 'Válida' : 'Abaixo do mínimo' ?></span>
                                            </div>
                                            <small><?= $esc($descarte['estacao']) ?><?= !empty($descarte['lixeira_codigo']) ? ' · ' . $esc($descarte['lixeira_codigo']) : '' ?> · <?= $esc($dataHora($descarte['criado_em'])) ?></small>
                                        </div>
                                        <div class="descartes-historico-numeros">
                                            <strong><?= $esc($decimal($descarte['quantidade'])) ?> <?= $esc($descarte['unidade']) ?></strong>
                                            <em><?= $valida ? '+' . $inteiro($descarte['pontos_gerados']) . ' pts' : 'sem pontos' ?></em>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                            <p class="descartes-legenda">O histórico mostra até as 80 pesagens mais recentes. Descartes abaixo do mínimo ficam registrados, mas não geram pontos.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="painel-app descartes-nova" aria-labelledby="nova-titulo">
                    <div class="descartes-nova-cabecalho">
                        <span class="etiqueta">Nova pesagem</span>
                        <h2 id="nova-titulo">Registrar descarte</h2>
                        <p>Selecione a lixeira usada, confira o código e informe o material pesado.</p>
                    </div>

                    <?php if (!$lixeiras): ?>
                        <p class="descartes-aviso" role="status">Nenhuma lixeira ativa disponível no momento. Tente novamente mais tarde.</p>
                    <?php elseif (!$materiais): ?>
                        <p class="descartes-aviso" role="status">Nenhum material aceito está ativo. Não é possível registrar uma pesagem agora.</p>
                    <?php else: ?>
                        <form class="descartes-form" method="post">
                            <?= Csrf::campoHtml() ?>
                            <input type="hidden" name="acao" value="registrar">

                            <label class="descartes-campo">Lixeira usada no descarte
                                <select id="descartes-lixeira" name="estacao_id" required>
                                    <option value="" <?= $lixeiraSelecionada < 1 ? 'selected' : '' ?> disabled>Selecione a lixeira</option>
                                    <?php foreach ($lixeiras as $lixeira): ?>
                                        <option value="<?= (int) $lixeira['id'] ?>"
                                            data-codigo="<?= $esc($lixeira['codigo']) ?>"
                                            data-nome="<?= $esc($lixeira['nome']) ?>"
                                            data-endereco="<?= $esc($lixeira['endereco'] ?? '') ?>"
                                            <?= (int) $lixeira['id'] === $lixeiraSelecionada ? 'selected' : '' ?>>
                                            <?= $esc($lixeira['codigo'] . ' · ' . $lixeira['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <div class="descartes-codigo" role="status" aria-live="polite">
                                <span>Código da lixeira</span>
                                <strong id="descartes-lixeira-codigo">—</strong>
                                <small id="descartes-lixeira-local">Selecione uma lixeira para ver o código.</small>
                            </div>

                            <label class="descartes-campo">Material
                                <select id="descartes-material" name="material_id" required>
                                    <?php foreach ($materiais as $material): ?>
                                        <option value="<?= (int) $material['id'] ?>"
                                            data-unidade="<?= $esc($material['unidade']) ?>"
                                            data-minimo="<?= $esc($material['quantidade_minima']) ?>"
                                            data-pontos="<?= $esc($material['pontos_por_unidade'] ?? 0) ?>"
                                            <?= (int) $material['id'] === $materialSelecionado ? 'selected' : '' ?>>
                                            <?= $esc($material['nome'] . ' (' . $material['unidade'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label class="descartes-campo">Quantidade pesada
                                <span class="descartes-campo-medida">
                                    <input id="descartes-quantidade" name="quantidade" type="number" min="0.001" step="0.001" inputmode="decimal" required value="<?= $esc($quantidadeInformada) ?>" placeholder="0,750">
                                    <em id="descartes-unidade"><?= $esc($materiais[0]['unidade'] ?? 'kg') ?></em>
                                </span>
                            </label>

                            <p class="descartes-minimo" id="descartes-minimo">
                                Mínimo para pontuar: <?= $esc($decimal($materiais[0]['quantidade_minima'] ?? 0)) ?> <?= $esc($materiais[0]['unidade'] ?? 'kg') ?>
                            </p>

                            <div class="descartes-previa">
                                <span>Pontos estimados</span>
                                <strong id="descartes-previa">0 pts</strong>
                            </div>

                            <button class="botao botao-primario descartes-botao" type="submit">Registrar pesagem</button>
                            <p class="descartes-ajuda">Os pontos são creditados automaticamente quando a quantidade atinge o mínimo do material.</p>
                        </form>
                    <?php endif; ?>
                </aside>
            </div>
        </main>
    </div>
</body>
</html>
