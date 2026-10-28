<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/AdminLixeirasRepository.php';

$usuario = Auth::exigirPapelPagina('admin', Url::para('/admin/login/'));
$paginaAtual = 'lixeiras';
header('Cache-Control: no-store');

$repo = new AdminLixeirasRepository();
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
                $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : 'Lixeira';
                $_SESSION['lixeiras_sucesso'] = 'Lixeira “' . $nome . '” cadastrada com o código ' . $criada['codigo'] . '.';
                header('Location: ' . Url::para('/admin/lixeiras/'), true, 303);
                exit;
            }

            if ($acao === 'status') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) {
                    throw new InvalidArgumentException('Lixeira inválida.');
                }
                $novoStatus = $repo->alternarStatus($id);
                $_SESSION['lixeiras_sucesso'] = $novoStatus === 'ativa'
                    ? 'Lixeira ativada. Ela já pode ser selecionada nas novas pesagens.'
                    : 'Lixeira desativada. Ela deixou de aparecer nas novas pesagens.';
                header('Location: ' . Url::para('/admin/lixeiras/'), true, 303);
                exit;
            }

            throw new InvalidArgumentException('Ação inválida.');
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $erro = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[EcoDrop] Falha ao salvar lixeira: ' . $e->getMessage());
            http_response_code(503);
            $erro = 'Não foi possível salvar a lixeira. Os dados preenchidos foram mantidos; tente novamente.';
        }
    }
}

$sucesso = $_SESSION['lixeiras_sucesso'] ?? '';
unset($_SESSION['lixeiras_sucesso']);

$dados = null;
$proximoCodigo = '—';
try {
    $dados = $repo->listar();
    $materiais = $repo->materiaisPorLixeira();
    $proximoCodigo = $repo->proximoCodigo();
} catch (Throwable $e) {
    error_log('[EcoDrop] Falha ao consultar lixeiras: ' . $e->getMessage());
    http_response_code(503);
    $erro = $erro ?: 'Não foi possível carregar as lixeiras. Tente novamente em instantes.';
    $materiais = [];
}

$esc = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$inteiro = static fn($valor): string => number_format((float) $valor, 0, ',', '.');
$decimal = static fn($valor): string => number_format((float) $valor, 3, ',', '.');
$data = static fn($valor): string => $valor ? date('d/m/Y H:i', strtotime((string) $valor)) : 'Nenhum descarte';
$textoPost = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
$valores = [
    'nome' => $acao === 'criar' ? $textoPost('nome') : '',
    'endereco' => $acao === 'criar' ? $textoPost('endereco') : '',
    'status' => $acao === 'criar' && isset(AdminLixeirasRepository::STATUS[$textoPost('status')])
        ? $textoPost('status')
        : 'ativa',
];

$lixeiras = $dados ?? [];
$porLixeira = [];
foreach ($materiais ?? [] as $material) {
    $porLixeira[(int) $material['estacao_id']][] = $material;
}
$indicadores = [
    'total' => count($lixeiras),
    'ativas' => count(array_filter($lixeiras, static fn(array $l): bool => $l['status'] === 'ativa')),
    'descartes' => (int) array_sum(array_column($lixeiras, 'registros')),
    'kg' => (float) array_sum(array_column($lixeiras, 'kg')),
    'litros' => (float) array_sum(array_column($lixeiras, 'litros')),
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lixeiras · EcoDrop Admin</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'admin', 'admin-lixeiras', 'theme'] as $css): ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="layout-admin">
    <?php include __DIR__ . '/../../components/navbar/navbar-admin.php'; ?>
    <main class="admin-conteudo lixeiras-pagina">
        <header class="admin-cabecalho">
            <div>
                <p class="lixeiras-sobretitulo">REDE DE COLETA</p>
                <h1>Lixeiras</h1>
                <p class="texto-suave">Cadastre os pontos de coleta e acompanhe o volume descartado em cada lixeira.</p>
            </div>
            <div class="flex itens-centro gap-3"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?><span><?= $esc($usuario['nome']) ?></span><?php $logoutClasse = 'botao botao-secundario'; require __DIR__ . '/../../components/app/logout-form.php'; ?></div>
        </header>

        <?php if ($erro): ?><p class="lixeiras-mensagem lixeiras-mensagem--erro" role="alert"><?= $esc($erro) ?></p><?php endif; ?>
        <?php if ($sucesso): ?><p class="lixeiras-mensagem" role="status"><?= $esc($sucesso) ?></p><?php endif; ?>

        <?php if ($dados !== null): ?>
        <section class="regua-indicadores lixeiras-indicadores" aria-label="Resumo das lixeiras">
            <div class="item"><p class="valor"><?= $inteiro($indicadores['total']) ?></p><p class="rotulo">Lixeiras cadastradas</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['ativas']) ?></p><p class="rotulo">Disponíveis para descarte</p></div>
            <div class="item"><p class="valor"><?= $inteiro($indicadores['descartes']) ?></p><p class="rotulo">Descartes registrados</p></div>
            <div class="item"><p class="valor"><?= $decimal($indicadores['kg']) ?></p><p class="rotulo">Sólidos recebidos · kg</p></div>
            <div class="item"><p class="valor"><?= $decimal($indicadores['litros']) ?></p><p class="rotulo">Líquidos recebidos · L</p></div>
        </section>

        <p class="lixeiras-nota"><strong>Como funciona:</strong> as lixeiras usam o mesmo cadastro das estações. Cada uma recebe um código público automático, exibido ao usuário após a seleção na nova pesagem. Lixeiras inativas deixam de aparecer no app.</p>

        <div class="lixeiras-layout">
            <section class="lixeiras-lista" aria-labelledby="lista-titulo">
                <div class="lixeiras-secao-cabecalho">
                    <div>
                        <h2 id="lista-titulo">Lixeiras existentes</h2>
                        <p class="texto-suave">Códigos, status e totais descartados em cada ponto.</p>
                    </div>
                    <a class="botao botao-primario" href="#nova-lixeira">Nova lixeira</a>
                </div>

                <?php if (!$lixeiras): ?>
                <div class="lixeiras-vazio">
                    <h3>Sua rede começa aqui</h3>
                    <p>Cadastre a primeira lixeira ao lado. O código será gerado automaticamente.</p>
                </div>
                <?php else: ?>
                <div class="lixeiras-grade">
                    <?php foreach ($lixeiras as $lixeira): ?>
                    <?php
                    $status = isset(AdminLixeirasRepository::STATUS[$lixeira['status']]) ? $lixeira['status'] : 'inativa';
                    $rotuloStatus = AdminLixeirasRepository::STATUS[$status];
                    $materiaisLixeira = $porLixeira[(int) $lixeira['id']] ?? [];
                    ?>
                    <article class="lixeiras-card">
                        <div class="lixeiras-card-topo">
                            <span class="lixeiras-codigo"><?= $esc($lixeira['codigo']) ?></span>
                            <span class="lixeiras-status lixeiras-status--<?= $esc($status) ?>"><?= $esc($rotuloStatus) ?></span>
                        </div>
                        <h3><?= $esc($lixeira['nome']) ?></h3>
                        <p class="lixeiras-endereco"><?= $esc($lixeira['endereco'] ?: 'Endereço não informado') ?></p>

                        <div class="lixeiras-metricas">
                            <div><strong><?= $inteiro($lixeira['registros']) ?></strong><span>descartes</span></div>
                            <div><strong><?= $decimal($lixeira['kg']) ?> <small>kg</small></strong><span>sólidos</span></div>
                            <div><strong><?= $decimal($lixeira['litros']) ?> <small>L</small></strong><span>líquidos</span></div>
                            <div><strong><?= $inteiro($lixeira['pontos']) ?></strong><span>pontos gerados</span></div>
                        </div>

                        <details>
                            <summary>Ver materiais descartados</summary>
                            <?php if (!$materiaisLixeira): ?>
                            <p class="texto-suave">Esta lixeira ainda não recebeu descartes válidos.</p>
                            <?php else: ?>
                            <ul class="lixeiras-materiais">
                                <?php foreach ($materiaisLixeira as $material): ?>
                                <li>
                                    <span><?= $esc($material['nome']) ?><small><?= $inteiro($material['registros']) ?> <?= (int) $material['registros'] === 1 ? 'descarte válido' : 'descartes válidos' ?></small></span>
                                    <strong><?= $decimal($material['quantidade']) ?> <?= $esc($material['unidade']) ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </details>

                        <footer>
                            <span>
                                <?= $inteiro($lixeira['validos']) ?> <?= (int) $lixeira['validos'] === 1 ? 'válido' : 'válidos' ?> · <?= $inteiro((int) $lixeira['registros'] - (int) $lixeira['validos']) ?> abaixo do mínimo<br>
                                Último descarte: <?= $esc($data($lixeira['ultimo'])) ?>
                            </span>
                            <form method="post" action="<?= Url::escapar('/admin/lixeiras/') ?>">
                                <?= Csrf::campoHtml() ?>
                                <input type="hidden" name="acao" value="status">
                                <input type="hidden" name="id" value="<?= (int) $lixeira['id'] ?>">
                                <button class="botao botao-secundario lixeiras-botao-status" type="submit"><?= $status === 'ativa' ? 'Desativar no app' : 'Ativar no app' ?></button>
                            </form>
                        </footer>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <aside class="lixeiras-form-painel" id="nova-lixeira" aria-labelledby="nova-titulo">
                <div class="lixeiras-form-cabecalho">
                    <span class="lixeiras-sobretitulo">CADASTRO</span>
                    <h2 id="nova-titulo">Nova lixeira</h2>
                    <p>Informe o nome e o endereço. O código público é gerado automaticamente.</p>
                </div>

                <div class="lixeiras-codigo-preview" role="status">
                    <span>Próximo código</span>
                    <strong><?= $esc($proximoCodigo) ?></strong>
                    <small>O código será confirmado ao salvar.</small>
                </div>

                <form method="post" action="<?= Url::escapar('/admin/lixeiras/') ?>" class="lixeiras-form">
                    <?= Csrf::campoHtml() ?>
                    <input type="hidden" name="acao" value="criar">

                    <label class="lixeiras-campo">Nome da lixeira
                        <input name="nome" type="text" maxlength="150" required value="<?= $esc($valores['nome']) ?>" placeholder="Ex.: EcoDrop Praça da Sé">
                    </label>

                    <label class="lixeiras-campo">Endereço
                        <input name="endereco" type="text" maxlength="255" value="<?= $esc($valores['endereco']) ?>" placeholder="Rua, número e referência">
                    </label>

                    <label class="lixeiras-campo">Disponibilidade
                        <select name="status">
                            <?php foreach (AdminLixeirasRepository::STATUS as $chave => $rotulo): ?>
                            <option value="<?= $esc($chave) ?>" <?= $valores['status'] === $chave ? 'selected' : '' ?>><?= $esc($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <p class="lixeiras-ajuda">Uma lixeira ativa aparece imediatamente na seleção de novas pesagens do app. Você pode desativá-la depois sem apagar o histórico.</p>
                    <button class="botao botao-primario lixeiras-form-botao" type="submit">Cadastrar lixeira</button>
                </form>
            </aside>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
