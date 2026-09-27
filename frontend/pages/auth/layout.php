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
  <?php require __DIR__ . '/../../components/brand/favicon.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $modoAuth === 'cadastro' ? 'Criar conta' : 'Entrar' ?> — EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/auth.css') ?>">
</head>
<body>
    <div class="auth-troca" data-auth-troca data-modo="<?= $modoAuth ?>">
        <main class="auth-formulario">
            <a class="eco-marca auth-marca-mobile" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
            <section class="auth-conteudo" data-auth-painel="login" aria-label="Entrar" <?= $modoAuth !== 'login' ? 'hidden inert' : '' ?>>
                <h1 tabindex="-1">Entrar</h1>
                <p class="subtitulo texto-suave">Acompanhe seus pontos e recompensas.</p>

                <?php $id = 'feedback-login'; include __DIR__ . '/../../components/alerts/alert.php'; ?>

                <form data-formulario="login" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="campo" data-campo="email">
                        <label for="login-email">E-mail</label>
                        <input type="email" id="login-email" name="email" required autocomplete="email">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="senha">
                        <label for="login-senha">Senha</label>
                        <input type="password" id="login-senha" name="senha" required autocomplete="current-password">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <button type="submit" class="botao botao-primario botao-bloco">Entrar</button>
                </form>

                <p class="rodape-autenticacao">Ainda não tem conta? <a data-auth-modo="cadastro" href="<?= Url::escapar('/app/cadastro/') ?>">Criar conta</a></p>

                <a class="auth-voltar" href="<?= Url::escapar('/') ?>">← Voltar ao início</a>
            </section>
            <section class="auth-conteudo" data-auth-painel="cadastro" aria-label="Criar conta" <?= $modoAuth !== 'cadastro' ? 'hidden inert' : '' ?>>
                <h1 tabindex="-1">Criar conta</h1>
                <p class="subtitulo texto-suave">Comece a acumular pontos pelos seus descartes.</p>

                <?php $id = 'feedback-cadastro'; include __DIR__ . '/../../components/alerts/alert.php'; ?>

                <form data-formulario="cadastro" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="campo" data-campo="nome">
                        <label for="cadastro-nome">Nome completo</label>
                        <input type="text" id="cadastro-nome" name="nome" required autocomplete="name">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="email">
                        <label for="cadastro-email">E-mail</label>
                        <input type="email" id="cadastro-email" name="email" required autocomplete="email">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="cpf">
                        <label for="cadastro-cpf">CPF</label>
                        <input type="text" id="cadastro-cpf" name="cpf" required inputmode="numeric" maxlength="14" placeholder="000.000.000-00">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="senha">
                        <label for="cadastro-senha">Senha</label>
                        <input type="password" id="cadastro-senha" name="senha" required minlength="8" autocomplete="new-password">
                        <span class="campo-ajuda">Mínimo de 8 caracteres.</span>
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <p class="campo-ajuda">Antes de criar sua conta, consulte os <a href="<?= Url::escapar('/termos-de-uso/') ?>">Termos de uso</a> e a <a href="<?= Url::escapar('/politica-de-privacidade/') ?>">Política de privacidade</a>.</p>
                    <button type="submit" class="botao botao-primario botao-bloco">Criar conta</button>
                </form>

                <p class="rodape-autenticacao">Já tem conta? <a data-auth-modo="login" href="<?= Url::escapar('/app/login/') ?>">Entrar</a></p>

                <a class="auth-voltar" href="<?= Url::escapar('/') ?>">← Voltar ao início</a>
            </section>
        </main>
        <aside class="auth-marca-painel" aria-label="EcoDrop">
            <a class="eco-marca eco-marca--clara" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
        </aside>
    </div>
    <script>
        window.EcoDropBaseUrl = <?= json_encode(Url::para('/'), JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= Url::escapar('/frontend/assets/js/api.js') ?>"></script>
    <script src="<?= Url::escapar('/frontend/assets/js/auth.js') ?>"></script>
    <script src="<?= Url::escapar('/frontend/assets/js/auth-transicao.js') ?>"></script>
</body>
</html>
