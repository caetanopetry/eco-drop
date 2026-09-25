<?php
/**
 * @var array $usuario Usuario autenticado (id, nome, email, papel).
 * @var string $paginaAtual Identificador da pagina atual.
 */
$paginaAtual = $paginaAtual ?? '';
?>
<header class="navbar-ecodrop">
    <div class="container">
        <a class="navbar-marca" href="<?= Url::escapar('/app/inicio/') ?>">Eco<em>Drop</em></a>
        <ul class="navbar-links">
            <li><a href="<?= Url::escapar('/app/inicio/') ?>" <?= $paginaAtual === 'inicio' ? 'aria-current="page"' : '' ?>>Início</a></li>
            <li><a href="<?= Url::escapar('/station/') ?>">Estação demo</a></li>
        </ul>
        <div class="navbar-usuario">
            <span class="texto-suave"><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></span>
            <a class="botao botao-secundario" href="<?= Url::escapar('/app/logout/') ?>">Sair</a>
        </div>
    </div>
</header>
