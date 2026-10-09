<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/services/PasswordRecoveryService.php';

Auth::iniciarSessao();

$feedback = $_SESSION['feedback_recuperacao'] ?? null;
unset($_SESSION['feedback_recuperacao']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validar(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        $_SESSION['feedback_recuperacao'] = [
            'tipo' => 'erro',
            'mensagem' => 'A solicitação expirou. Recarregue a página e tente novamente.',
        ];
    } else {
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
            $_SESSION['feedback_recuperacao'] = [
                'tipo' => 'erro',
                'mensagem' => 'Informe um e-mail válido.',
            ];
        } else {
            try {
                (new PasswordRecoveryService())->solicitar($email);
            } catch (EmailDeliveryException $e) {
                error_log('[EcoDrop] Recuperação de senha: ' . $e->getMessage());
            }

            $_SESSION['feedback_recuperacao'] = [
                'tipo' => 'sucesso',
                'mensagem' => 'Se o e-mail estiver cadastrado, você receberá as instruções para redefinir sua senha.',
            ];
        }
    }

    header('Location: ' . Url::para('/app/esqueci-senha/'), true, 303);
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../../components/theme/head.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Esqueci minha senha — EcoDrop</title>
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
                <h1 id="titulo-recuperacao">Esqueci minha senha</h1>
                <p class="subtitulo texto-suave">Informe o e-mail da sua conta. Se ele estiver cadastrado, enviaremos um link para redefinir sua senha.</p>

                <?php if (is_array($feedback)): ?>
                    <div class="alerta alerta-<?= htmlspecialchars((string) $feedback['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $feedback['tipo'] === 'erro' ? 'alert' : 'status' ?>">
                        <?= htmlspecialchars((string) $feedback['mensagem'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= Url::escapar('/app/esqueci-senha/') ?>">
                    <?= Csrf::campoHtml() ?>
                    <div class="campo">
                        <label for="recuperacao-email">E-mail</label>
                        <input type="email" id="recuperacao-email" name="email" required maxlength="190" autocomplete="email">
                    </div>
                    <button type="submit" class="botao botao-primario botao-bloco">Enviar instruções</button>
                </form>

                <p class="rodape-autenticacao"><a href="<?= Url::escapar('/app/login/') ?>">Voltar para entrar</a></p>
            </section>
        </main>
        <aside class="auth-marca-painel" aria-label="EcoDrop">
            <a class="eco-marca eco-marca--clara" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
        </aside>
    </div>
</body>
</html>
