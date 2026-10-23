<?php
/**
 * @var string $paginaAtualApp
 */
$paginaAtualApp = $paginaAtualApp ?? 'inicio';

if (!function_exists('linkAppAtivo')) {
    function linkAppAtivo(string $chave, string $atual): string
    {
        return $chave === $atual ? 'aria-current="page"' : '';
    }
}
?>
<aside class="app-sidebar">
    <a class="app-marca eco-marca" href="<?= Url::escapar('/app/inicio/') ?>"><?php require __DIR__ . '/../brand/marca.php'; ?></a>
    <nav class="app-menu" aria-label="Navegacao do app">
        <span>Cliente</span>
        <a href="<?= Url::escapar('/app/inicio/') ?>" <?= linkAppAtivo('inicio', $paginaAtualApp) ?>>Visao geral</a>
        <a href="<?= Url::escapar('/app/saques/') ?>" <?= linkAppAtivo('saques', $paginaAtualApp) ?>>Resgates</a>
        <a href="<?= Url::escapar('/app/extrato/') ?>" <?= linkAppAtivo('extrato', $paginaAtualApp) ?>>Extrato</a>
        <a href="<?= Url::escapar('/app/conta/') ?>" <?= linkAppAtivo('conta', $paginaAtualApp) ?>>Conta</a>
    </nav>
    <div class="app-sidebar-rodape">
        <?php require __DIR__ . '/logout-form.php'; ?>
    </div>
</aside>
