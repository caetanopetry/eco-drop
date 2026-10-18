<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/services/PasswordRecoveryService.php';

Auth::iniciarSessao();
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$service = new PasswordRecoveryService();
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Ao abrir o link do e-mail (GET ?token=...), o token é guardado na sessão e a
// página redireciona para a URL limpa, evitando mantê-lo na barra de endereços,
// no histórico do navegador e no cabeçalho Referer de recursos externos.
if ($metodo === 'GET' && isset($_GET['token'])) {
    $tokenLink = is_string($_GET['token']) ? $_GET['token'] : '';
    if ($service->tokenValido($tokenLink)) {
        $_SESSION['redefinir_token'] = $tokenLink;
    } else {
        $_SESSION['feedback_redefinir_senha'] = [
            'tipo' => 'erro',
            'mensagem' => 'Este link é inválido, expirou ou já foi utilizado. Solicite uma nova redefinição.',
        ];
    }

    header('Location: ' . Url::para('/app/redefinir-senha/'), true, 303);
    exit;
}

$tokenRecebido = is_string($_SESSION['redefinir_token'] ?? null) ? $_SESSION['redefinir_token'] : '';
$feedback = $_SESSION['feedback_redefinir_senha'] ?? null;
unset($_SESSION['feedback_redefinir_senha']);
$tokenValido = $tokenRecebido !== '' && $service->tokenValido($tokenRecebido);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        $feedback = [
            'tipo' => 'erro',
            'mensagem' => 'A solicitação expirou. Recarregue a página e tente novamente.',
        ];
    } elseif (!$tokenValido) {
        $feedback = [
            'tipo' => 'erro',
            'mensagem' => 'Este link é inválido, expirou ou já foi utilizado. Solicite uma nova redefinição.',
        ];
    } else {
        $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
        $confirmarSenha = is_string($_POST['confirmar_senha'] ?? null) ? $_POST['confirmar_senha'] : '';

        if (!Validator::senhaForteMinima($senha)) {
            $feedback = [
                'tipo' => 'erro',
                'mensagem' => 'A senha deve ter no mínimo 8 caracteres.',
            ];
        } elseif ($confirmarSenha === '' || !hash_equals($senha, $confirmarSenha)) {
            $feedback = [
                'tipo' => 'erro',
                'mensagem' => 'As senhas não conferem.',
            ];
        } elseif ($service->redefinirSenha($tokenRecebido, $senha)) {
            unset($_SESSION['redefinir_token']);
            $_SESSION['feedback_login'] = [
                'tipo' => 'sucesso',
                'mensagem' => 'Senha redefinida com sucesso. Entre com sua nova senha.',
            ];
            header('Location: ' . Url::para('/app/login/'), true, 303);
            exit;
        } else {
            $feedback = [
                'tipo' => 'erro',
                'mensagem' => 'Este link é inválido, expirou ou já foi utilizado. Solicite uma nova redefinição.',
            ];
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redefinir senha — EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/auth.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/auth.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/theme.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme.css') ?>">
</head>
<body>
    <div class="tema-acesso"><?php require __DIR__ . '/../../components/theme/toggle.php'; ?></div>
    <div class="auth-troca auth-recuperacao">
        <main class="auth-formulario">
            <a class="eco-marca auth-marca-mobile" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
            <section class="auth-conteudo" aria-labelledby="titulo-recuperacao">
                <h1 id="titulo-recuperacao">Redefinir senha</h1>
                <p class="subtitulo texto-suave">Escolha uma nova senha para sua conta EcoDrop.</p>

                <?php if ($tokenValido && is_array($feedback)): ?>
                    <div class="alerta alerta-<?= htmlspecialchars((string) $feedback['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="alert">
                        <?= htmlspecialchars((string) $feedback['mensagem'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($tokenValido): ?>
                    <form method="post" action="<?= Url::escapar('/app/redefinir-senha/') ?>">
                        <?= Csrf::campoHtml() ?>
                        <div class="campo">
                            <label for="nova-senha">Nova senha</label>
                            <input type="password" id="nova-senha" name="senha" required minlength="8" autocomplete="new-password">
                            <span class="campo-ajuda">Mínimo de 8 caracteres.</span>
                        </div>
                        <div class="campo">
                            <label for="confirmar-senha">Confirme a nova senha</label>
                            <input type="password" id="confirmar-senha" name="confirmar_senha" required minlength="8" autocomplete="new-password">
                        </div>
                        <button type="submit" class="botao botao-primario botao-bloco">Salvar nova senha</button>
                    </form>
                <?php else: ?>
                    <div class="alerta alerta-erro" role="alert">
                        Este link é inválido, expirou ou já foi utilizado. Solicite uma nova redefinição.
                    </div>
                    <a class="botao botao-primario botao-bloco" href="<?= Url::escapar('/app/esqueci-senha/') ?>">Solicitar novo link</a>
                <?php endif; ?>

                <p class="rodape-autenticacao"><a href="<?= Url::escapar('/app/login/') ?>">Voltar para entrar</a></p>
            </section>
        </main>
        <aside class="auth-marca-painel" aria-label="EcoDrop">
            <a class="eco-marca eco-marca--clara" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
        </aside>
    </div>
</body>
</html>
