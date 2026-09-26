<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';

Auth::iniciarSessao();

$usuarioLogado = Auth::usuarioLogado();
if ($usuarioLogado !== null) {
    header('Location: ' . ($usuarioLogado['papel'] === 'admin' ? Url::para('/admin/dashboard/') : Url::para('/app/inicio/')));
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>">
</head>
<body>
    <div class="tela-dividida">
        <aside class="tela-dividida-editorial">
            <a class="marca eco-marca eco-marca--clara" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
            <p class="frase">Cada descarte registrado é um ponto que não some.</p>
            <p class="rodape-frase">Coleta seletiva incentivada</p>
        </aside>

        <main class="tela-dividida-formulario">
            <div class="miolo">
                <h1>Entrar</h1>
                <p class="subtitulo texto-suave">Acompanhe seus pontos e recompensas.</p>

                <?php include __DIR__ . '/../../components/alerts/alert.php'; ?>

                <form data-formulario="login" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="campo" data-campo="email">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" required autocomplete="email">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="senha">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required autocomplete="current-password">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <button type="submit" class="botao botao-primario botao-bloco">Entrar</button>
                </form>

                <p class="rodape-autenticacao">Ainda não tem conta? <a href="<?= Url::escapar('/app/cadastro/') ?>">Criar conta</a></p>
            </div>
        </main>
    </div>

    <script>
        window.EcoDropBaseUrl = <?= json_encode(Url::para('/'), JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= Url::escapar('/frontend/assets/js/api.js') ?>"></script>
    <script src="<?= Url::escapar('/frontend/assets/js/auth.js') ?>"></script>
</body>
</html>
