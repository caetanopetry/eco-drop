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
$sucesso = $_SESSION['conta_sucesso'] ?? null;
unset($_SESSION['conta_sucesso']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !Csrf::validar($token)) {
        http_response_code(419);
        $erro = 'Recarregue a página e tente novamente.';
    } else {
        try {
            $foto = FotoPerfil::processarUpload(is_array($_FILES['foto'] ?? null) ? $_FILES['foto'] : []);
            $repo->salvarFotoPersonalizada((int) $usuario['id'], $foto);
            $_SESSION['conta_sucesso'] = 'Foto atualizada com sucesso.';
            header('Location: ' . Url::para('/app/conta/'), true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $erro = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[EcoDrop] Falha ao salvar foto (' . get_class($e) . ').');
            http_response_code(500);
            $erro = 'Não foi possível salvar a foto. Tente novamente.';
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
    'Membro desde' => date('d/m/Y', strtotime($perfil['criado_em'])),
    'Formas de acesso' => $perfil['google_sub'] ? 'Google e e-mail com senha' : 'E-mail e senha',
    'Situação da conta' => 'Ativa',
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conta - EcoDrop</title>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php foreach (['variables', 'global', 'components', 'app'] as $css): ?>
        <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/' . $css . '.css') ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/conta.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/conta.css') ?>">
    <script src="<?= Url::escapar('/frontend/assets/js/conta.js') ?>" defer></script>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../../components/app/sidebar.php'; ?>
    <main class="conteudo-app pagina-conta">
        <header class="conta-cabecalho">
            <div><span class="conta-sobretitulo">Meu perfil</span><h1>Conta</h1><p>Seu espaço no EcoDrop. Todos os seus dados em um só lugar.</p></div>
            <span class="conta-status"><span aria-hidden="true"></span>Conta ativa</span>
        </header>
        <?php if ($erro !== null): ?>
            <div class="alerta alerta-erro" role="alert"><?= $esc($erro) ?></div>
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
                <span class="etiqueta"><?= $perfil['google_sub'] ? 'Google conectado' : 'Conta EcoDrop' ?></span>
                <dl class="conta-resumo">
                    <div><dt>E-mail</dt><dd><?= $esc($perfil['email']) ?></dd></div>
                    <div><dt>Membro desde</dt><dd><?= $esc($dados['Membro desde']) ?></dd></div>
                </dl>
                <form method="post" action="<?= Url::escapar('/app/conta/') ?>" enctype="multipart/form-data" class="conta-foto-form">
                    <?= Csrf::campoHtml() ?>
                    <label for="foto">Alterar foto de perfil</label>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" aria-describedby="foto-ajuda" required>
                    <p id="foto-ajuda">JPG, PNG ou WebP. Até 2 MB e 4096 × 4096 pixels.</p>
                    <button class="botao botao-primario" type="submit">Salvar foto</button>
                </form>
                <?php if ($perfil['google_sub'] && !$perfil['foto_google'] && !$perfil['foto_personalizada']): ?>
                    <p class="conta-nota">Entre novamente com o Google para importar sua foto ou envie uma imagem acima.</p>
                <?php endif; ?>
            </section>
            <section class="conta-detalhes" aria-labelledby="titulo-dados">
                <nav class="conta-navegacao" aria-label="Seções da conta">
                    <a href="<?= Url::escapar('/app/conta/') ?>" aria-current="page">Dados pessoais</a>
                    <a href="<?= Url::escapar('/app/extrato/') ?>">Extrato</a>
                    <a href="<?= Url::escapar('/app/saques/') ?>">Saques</a>
                </nav>
                <div class="conta-informacoes">
                <div class="conta-secao-cabecalho"><span class="conta-sobretitulo">Meu cadastro</span><h2 id="titulo-dados">Dados pessoais</h2><p>Informações associadas à sua conta EcoDrop.</p></div>
                <dl class="conta-dados">
                    <?php foreach ($dados as $rotulo => $valor): ?>
                        <div><dt><?= $esc($rotulo) ?></dt><dd><?= $esc($valor) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
                <div class="conta-rodape-dados"><span class="conta-status"><span aria-hidden="true"></span><?= $perfil['google_sub'] ? 'Conta vinculada ao Google' : 'Cadastro com e-mail e senha' ?></span><p>Seus dados acompanham você em cada novo descarte.</p></div>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
