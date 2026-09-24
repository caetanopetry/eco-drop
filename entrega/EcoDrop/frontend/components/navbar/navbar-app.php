<?php
/**
 * @var array $usuario Usuário autenticado (id, nome, email, papel) — vem de Auth::usuarioLogado().
 * @var string $paginaAtual Identificador da página atual, para destacar o link ativo.
 */
$paginaAtual = $paginaAtual ?? '';
?>
<header class="navbar-ecodrop">
    <div class="container">
        <a class="navbar-marca" href="<?= Url::escapar('/app/inicio/') ?>">Eco<em>Drop</em></a>
        <ul class="navbar-links">
            <li><a href="<?= Url::escapar('/app/inicio/') ?>" <?= $paginaAtual === 'inicio' ? 'aria-current="page"' : '' ?>>Início</a></li>
        </ul>
        <div class="navbar-usuario">
            <span class="texto-suave"><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></span>
            <a class="botao botao-secundario" href="<?= Url::escapar('/app/logout/') ?>">Sair</a>
        </div>
    </div>
</header>
