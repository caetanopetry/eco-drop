<?php
/**
 * @var string $paginaAtual Identificador da página atual, para destacar o link ativo.
 */
$paginaAtual = $paginaAtual ?? '';

function linkAdminAtivo(string $chave, string $atual): string
{
    return $chave === $atual ? 'aria-current="page"' : '';
}
?>
<aside class="admin-sidebar">
    <a class="marca eco-marca" href="<?= Url::escapar('/admin/dashboard/') ?>"><?php require __DIR__ . '/../brand/marca.php'; ?> <span class="marca-selo">Admin</span></a>
    <nav aria-label="Navegação administrativa">
        <ul>
            <li><a href="<?= Url::escapar('/admin/dashboard/') ?>" <?= linkAdminAtivo('dashboard', $paginaAtual) ?>>Dashboard</a></li>
            <li><a href="<?= Url::escapar('/admin/usuarios/') ?>" <?= linkAdminAtivo('usuarios', $paginaAtual) ?>>Usuários</a></li>
            <li><a href="<?= Url::escapar('/admin/estacoes/') ?>" <?= linkAdminAtivo('estacoes', $paginaAtual) ?>>Estações</a></li>
            <li><a href="<?= Url::escapar('/admin/materiais/') ?>" <?= linkAdminAtivo('materiais', $paginaAtual) ?>>Materiais</a></li>
            <li><a href="<?= Url::escapar('/admin/recompensas/') ?>" <?= linkAdminAtivo('recompensas', $paginaAtual) ?>>Recompensas</a></li>
            <li><a href="<?= Url::escapar('/admin/parceiros/') ?>" <?= linkAdminAtivo('parceiros', $paginaAtual) ?>>Parceiros</a></li>
            <li><a href="<?= Url::escapar('/admin/ranking/') ?>" <?= linkAdminAtivo('ranking', $paginaAtual) ?>>Ranking</a></li>
        </ul>
    </nav>
</aside>
