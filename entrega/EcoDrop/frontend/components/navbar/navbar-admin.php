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
    <a class="marca" href="<?= Url::escapar('/admin/dashboard/') ?>">Eco<em>Drop</em> <span style="opacity:.6; font-size:.8rem;">Admin</span></a>
    <nav aria-label="Navegação administrativa">
        <ul>
            <li><a href="<?= Url::escapar('/admin/dashboard/') ?>" <?= linkAdminAtivo('dashboard', $paginaAtual) ?>>Dashboard</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Usuários</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Estações</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Materiais</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Recompensas</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Parceiros</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Vouchers</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Ranking</a></li>
            <li><a href="#" aria-disabled="true" title="Disponível em uma próxima fase">Premiações</a></li>
        </ul>
    </nav>
</aside>
