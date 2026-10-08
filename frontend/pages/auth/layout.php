<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';

Auth::iniciarSessao();

$erroGoogle = null;
if ($modoAuth === 'login' && isset($_SESSION['google_oauth_erro'])) {
    $erroGoogle = (string) $_SESSION['google_oauth_erro'];
    unset($_SESSION['google_oauth_erro']);
}

$feedbackLogin = $modoAuth === 'login' ? ($_SESSION['feedback_login'] ?? null) : null;
if ($modoAuth === 'login') {
    unset($_SESSION['feedback_login']);
}

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
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/auth.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/auth.css') ?>-<?= filesize(__DIR__ . '/../../assets/css/auth.css') ?>">
</head>
<body>
    <div class="auth-troca" data-auth-troca data-modo="<?= $modoAuth ?>">
        <main class="auth-formulario">
            <a class="eco-marca auth-marca-mobile" href="<?= Url::escapar('/') ?>"><?php require __DIR__ . '/../../components/brand/marca.php'; ?></a>
            <section class="auth-conteudo" data-auth-painel="login" aria-label="Entrar" <?= $modoAuth !== 'login' ? 'hidden inert' : '' ?>>
                <h1 tabindex="-1">Entrar</h1>
                <p class="subtitulo texto-suave">Acompanhe seus pontos e recompensas.</p>

                <?php if (is_array($feedbackLogin)): ?>
                    <div class="alerta alerta-<?= htmlspecialchars((string) $feedbackLogin['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="status">
                        <?= htmlspecialchars((string) $feedbackLogin['mensagem'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php $id = 'feedback-login'; include __DIR__ . '/../../components/alerts/alert.php'; ?>
                <?php if ($erroGoogle !== null): ?>
                    <div class="alerta alerta-erro" role="alert">
                        <?= htmlspecialchars($erroGoogle, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form data-formulario="login" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="campo" data-campo="email">
                        <label for="login-email">E-mail</label>
                        <input type="email" id="login-email" name="email" required autocomplete="email">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="senha">
                        <label for="login-senha">Senha</label>
                        <div class="campo-senha-controle">
                            <input type="password" id="login-senha" name="senha" required autocomplete="current-password">
                            <button type="button" class="alternar-senha" data-toggle-senha data-visivel="false" aria-controls="login-senha" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">
                                <svg class="icone-olho--aberto" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icone-olho--fechado" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a13.8 13.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3-.4"/></svg>
                            </button>
                        </div>
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <p class="auth-esqueci-senha"><a href="<?= Url::escapar('/app/esqueci-senha/') ?>">Esqueci minha senha</a></p>
                    <button type="submit" class="botao botao-primario botao-bloco">Entrar</button>
                </form>

                <a class="botao botao-secundario botao-bloco auth-google" href="<?= Url::escapar('/auth/google') ?>">
                    <svg class="auth-google__icone" aria-hidden="true" viewBox="0 0 48 48" focusable="false">
                        <path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.8-.4-4.1H24v7.8h11c-.5 2.5-2 4.6-4.2 6l6.8 5.3c4-3.7 6-9.1 6-15Z"/>
                        <path fill="#34A853" d="M24 44c5.5 0 10.2-1.8 13.6-4.9l-6.8-5.3c-1.8 1.2-4.1 2-6.8 2-5.2 0-9.6-3.5-11.2-8.2l-7 5.4C7.7 39.6 15.3 44 24 44Z"/>
                        <path fill="#FBBC05" d="M12.8 27.6c-.4-1.1-.7-2.3-.7-3.6s.2-2.5.7-3.6l-7-5.4C4.6 17.5 4 20.6 4 24s.6 6.5 1.8 9l7-5.4Z"/>
                        <path fill="#EA4335" d="M24 12.6c3 0 5.7 1 7.8 3l5.9-5.9C34.2 6.2 29.4 4 24 4 15.3 4 7.7 8.4 5.8 15l7 5.4c1.6-4.7 6-7.8 11.2-7.8Z"/>
                    </svg>
                    <span>Entrar com Google</span>
                </a>

                <p class="rodape-autenticacao">Ainda não tem conta? <a data-auth-modo="cadastro" href="<?= Url::escapar('/app/cadastro/') ?>">Criar conta</a></p>

                <a class="auth-voltar" href="<?= Url::escapar('/') ?>">← Voltar ao início</a>
            </section>
            <section class="auth-conteudo" data-auth-painel="cadastro" aria-label="Criar conta" <?= $modoAuth !== 'cadastro' ? 'hidden inert' : '' ?>>
                <h1 tabindex="-1">Criar conta</h1>
                <p class="subtitulo texto-suave">Comece a acumular pontos pelos seus descartes.</p>

                <?php $id = 'feedback-cadastro'; include __DIR__ . '/../../components/alerts/alert.php'; ?>

                <form data-formulario="cadastro">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="campo" data-campo="nome">
                        <label for="cadastro-nome">Nome completo</label>
                        <input type="text" id="cadastro-nome" name="nome" required autocomplete="name">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="email">
                        <label for="cadastro-email">E-mail</label>
                        <input type="email" id="cadastro-email" name="email" required maxlength="190" autocomplete="email">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="cpf">
                        <label for="cadastro-cpf">CPF</label>
                        <input type="text" id="cadastro-cpf" name="cpf" required inputmode="numeric" maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" placeholder="000.000.000-00" title="Informe um CPF no formato 000.000.000-00">
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <div class="campo" data-campo="senha">
                        <label for="cadastro-senha">Senha</label>
                        <div class="campo-senha-controle">
                            <input type="password" id="cadastro-senha" name="senha" required minlength="8" autocomplete="new-password" aria-describedby="cadastro-senha-ajuda">
                            <button type="button" class="alternar-senha" data-toggle-senha data-visivel="false" aria-controls="cadastro-senha" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">
                                <svg class="icone-olho--aberto" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icone-olho--fechado" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a13.8 13.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3-.4"/></svg>
                            </button>
                        </div>
                        <span class="campo-ajuda" id="cadastro-senha-ajuda">Mínimo de 8 caracteres.</span>
                        <span class="campo-erro-texto" data-erro-campo></span>
                    </div>

                    <p class="campo-ajuda auth-termos">Consulte a <a href="<?= Url::escapar('/politica-de-privacidade/') ?>" target="_blank" rel="noopener">Política de privacidade</a> para saber como seus dados são tratados e os <a href="<?= Url::escapar('/termos-de-uso/') ?>" target="_blank" rel="noopener">Termos de uso</a>.</p>
                    <button type="submit" class="botao botao-primario botao-bloco">Criar conta</button>
                </form>

                <a class="botao botao-secundario botao-bloco auth-google" href="<?= Url::escapar('/auth/google') ?>">
                    <svg class="auth-google__icone" aria-hidden="true" viewBox="0 0 48 48" focusable="false">
                        <path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.8-.4-4.1H24v7.8h11c-.5 2.5-2 4.6-4.2 6l6.8 5.3c4-3.7 6-9.1 6-15Z"/>
                        <path fill="#34A853" d="M24 44c5.5 0 10.2-1.8 13.6-4.9l-6.8-5.3c-1.8 1.2-4.1 2-6.8 2-5.2 0-9.6-3.5-11.2-8.2l-7 5.4C7.7 39.6 15.3 44 24 44Z"/>
                        <path fill="#FBBC05" d="M12.8 27.6c-.4-1.1-.7-2.3-.7-3.6s.2-2.5.7-3.6l-7-5.4C4.6 17.5 4 20.6 4 24s.6 6.5 1.8 9l7-5.4Z"/>
                        <path fill="#EA4335" d="M24 12.6c3 0 5.7 1 7.8 3l5.9-5.9C34.2 6.2 29.4 4 24 4 15.3 4 7.7 8.4 5.8 15l7 5.4c1.6-4.7 6-7.8 11.2-7.8Z"/>
                    </svg>
                    <span>Entrar com Google</span>
                </a>

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
    <script src="<?= Url::escapar('/frontend/assets/js/auth.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/auth.js') ?>"></script>
    <script src="<?= Url::escapar('/frontend/assets/js/auth-transicao.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/auth-transicao.js') ?>"></script>
</body>
</html>
