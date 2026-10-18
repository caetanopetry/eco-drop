<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/repositories/UsuarioRepository.php';
require_once __DIR__ . '/../../../backend/services/FotoPerfil.php';

Auth::iniciarSessao();
$usuario = Auth::exigirAutenticacaoPagina(Url::para('/app/login/'));
if ($usuario['papel'] === 'admin') {
    header('Location: ' . Url::para('/admin/dashboard/'));
    exit;
}
header('Cache-Control: no-store');
$repo = new UsuarioRepository();
$perfil = $repo->buscarPerfil((int) $usuario['id']);
if ($perfil === null || $perfil['status'] !== 'ativo') {
    Auth::encerrarSessao();
    header('Location: ' . Url::para('/app/login/'));
    exit;
}
$erro = null;
$acao = '';
$nomeFormulario = $perfil['nome'];
$sucesso = $_SESSION['conta_sucesso'] ?? null;
unset($_SESSION['conta_sucesso']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $acaoRecebida = $_POST['acao'] ?? 'alterar_foto';
    $acao = is_string($acaoRecebida) ? $acaoRecebida : '';
    if ($acao === 'alterar_nome') {
        $nomeFormulario = is_string($_POST['nome'] ?? null) ? $_POST['nome'] : '';
    }
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !Csrf::validar($token)) {
        http_response_code(403);
        $erro = 'Recarregue a página e tente novamente.';
    } else {
        try {
            if ($acao === 'alterar_nome') {
                if (!mb_check_encoding($nomeFormulario, 'UTF-8')) {
                    throw new InvalidArgumentException('Informe um nome válido.');
                }
                $nome = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $nomeFormulario) ?? '');
                if ($nome === '' || mb_strlen($nome, 'UTF-8') > 150) {
                    throw new InvalidArgumentException('Informe seu nome com até 150 caracteres.');
                }
                if ($nome === $perfil['nome']) {
                    $_SESSION['conta_sucesso'] = 'Seu nome já está atualizado.';
                } else {
                    $repo->salvarNome((int) $usuario['id'], $nome);
                    $_SESSION['usuario']['nome'] = $nome;
                    $_SESSION['conta_sucesso'] = 'Nome atualizado com sucesso.';
                }
            } elseif ($acao === 'alterar_foto') {
                $foto = FotoPerfil::processarUpload(is_array($_FILES['foto'] ?? null) ? $_FILES['foto'] : []);
                $repo->salvarFotoPersonalizada((int) $usuario['id'], $foto);
                $_SESSION['conta_sucesso'] = 'Foto atualizada com sucesso.';
            } else {
                throw new InvalidArgumentException('Ação inválida. Recarregue a página e tente novamente.');
            }
            header('Location: ' . Url::para('/app/conta/'), true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $erro = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[EcoDrop] Falha ao atualizar perfil (' . get_class($e) . ').');
            http_response_code(500);
            $erro = 'Não foi possível salvar a alteração. Tente novamente.';
        }
    }
}
$paginaAtualApp = 'conta';
$esc = static fn ($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$foto = $perfil['foto_personalizada'] ?: $perfil['foto_google'];
$iniciais = mb_strtoupper(mb_substr(trim($perfil['nome']), 0, 1));
$cpf = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $perfil['cpf']);
$dados = [
    'Nome completo' => $perfil['nome'],
    'E-mail' => $perfil['email'],
    'CPF' => $cpf,
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conta - EcoDrop</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'app'] as $css): ?>
        <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/conta.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/conta.css') ?>">
    <script src="<?= Url::escapar('/frontend/assets/js/conta.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/conta.js') ?>" defer></script>
    <script src="<?= Url::escapar('/frontend/assets/js/conta-nome.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/conta-nome.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../../components/app/sidebar.php'; ?>
    <main class="conteudo-app pagina-conta">
        <header class="conta-cabecalho">
            <div><span class="conta-sobretitulo">Meu perfil</span><h1>Conta</h1><p>Atualize seu nome e sua foto de perfil.</p></div>
            <div class="perfil-controles"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?></div>
        </header>
        <?php if ($erro !== null): ?>
            <div class="alerta alerta-erro" id="conta-erro" role="alert"><?= $esc($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso !== null): ?>
            <div class="alerta alerta-sucesso" role="status"><?= $esc($sucesso) ?></div>
        <?php endif; ?>
        <div class="conta-grade">
            <section class="painel-app conta-perfil" aria-labelledby="titulo-perfil">
                <div class="conta-avatar">
                    <span role="img" aria-label="Inicial do seu nome"><?= $esc($iniciais) ?></span>
                    <?php if ($foto): ?>
                        <img src="<?= $esc($foto) ?>" alt="Sua foto de perfil" width="184" height="184" referrerpolicy="no-referrer">
                    <?php endif; ?>
                </div>
                <h2 id="titulo-perfil"><?= $esc($perfil['nome']) ?></h2>
                <dl class="conta-resumo">
                    <div><dt>Membro desde</dt><dd><?= $esc(date('d/m/Y', strtotime($perfil['criado_em']))) ?></dd></div>
                </dl>
                <details class="conta-foto-editor" <?= $erro !== null && $acao === 'alterar_foto' ? 'open' : '' ?>>
                <summary class="botao botao-secundario">Alterar foto</summary>
                <form method="post" action="<?= Url::escapar('/app/conta/') ?>" enctype="multipart/form-data" class="conta-foto-form">
                    <?= Csrf::campoHtml() ?>
                    <input type="hidden" name="acao" value="alterar_foto">
                    <label for="foto">Escolher nova foto</label>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" aria-describedby="foto-ajuda foto-feedback" required>
                    <p id="foto-ajuda">JPG, PNG ou WebP. Até 2 MB e 4096 × 4096 pixels.</p>
                    <div class="conta-foto-previa" hidden><img alt="Prévia da nova foto de perfil" width="160" height="160"><p>Prévia. A foto só será alterada ao salvar.</p></div>
                    <p id="foto-feedback" role="status" aria-live="polite"></p>
                    <button class="botao botao-primario" type="submit">Salvar foto</button>
                    <button class="botao botao-secundario" type="button" data-cancelar-foto hidden>Cancelar</button>
                </form>
                </details>
            </section>
            <section class="conta-detalhes" aria-labelledby="titulo-dados">
                <div class="conta-informacoes">
                <div class="conta-secao-cabecalho"><span class="conta-sobretitulo">Meu cadastro</span><h2 id="titulo-dados">Dados pessoais</h2><p>Você pode alterar seu nome. E-mail e CPF estão disponíveis para consulta.</p></div>
                <dl class="conta-dados">
                    <?php foreach ($dados as $rotulo => $valor): ?>
                        <div><dt><?= $esc($rotulo) ?></dt><dd>
                            <?php if ($rotulo === 'Nome completo'): ?>
                                <details class="conta-nome-editor" <?= $erro !== null && $acao === 'alterar_nome' ? 'open' : '' ?>>
                                    <summary><span><?= $esc($valor) ?></span><span class="botao botao-secundario conta-nome-acionar">Alterar nome</span></summary>
                                    <form method="post" action="<?= Url::escapar('/app/conta/') ?>" class="conta-nome-form">
                                        <?= Csrf::campoHtml() ?>
                                        <input type="hidden" name="acao" value="alterar_nome">
                                        <label for="nome">Nome completo</label>
                                        <input id="nome" name="nome" type="text" value="<?= $esc($nomeFormulario) ?>" data-nome-atual="<?= $esc($perfil['nome']) ?>" autocomplete="name" maxlength="150" required aria-describedby="nome-ajuda nome-feedback<?= $erro !== null && $acao === 'alterar_nome' ? ' conta-erro' : '' ?>">
                                        <p id="nome-ajuda">Até 150 caracteres. Esse nome será exibido no seu perfil.</p>
                                        <p id="nome-feedback" role="status" aria-live="polite"></p>
                                        <div class="conta-nome-acoes">
                                            <button class="botao botao-primario" type="submit">Salvar nome</button>
                                            <a class="botao botao-secundario" href="<?= Url::escapar('/app/conta/') ?>" data-cancelar-nome>Cancelar</a>
                                        </div>
                                    </form>
                                </details>
                            <?php else: ?>
                                <?= $esc($valor) ?>
                            <?php endif; ?>
                        </dd></div>
                    <?php endforeach; ?>
                </dl>
                <nav class="conta-atalhos" aria-label="Pontos e recompensas">
                    <a class="botao botao-secundario" href="<?= Url::escapar('/app/extrato/') ?>">Ver extrato <span aria-hidden="true">→</span></a>
                    <a class="botao botao-secundario" href="<?= Url::escapar('/app/saques/') ?>">Ir para saques <span aria-hidden="true">→</span></a>
                </nav>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
