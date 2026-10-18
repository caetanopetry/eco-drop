<?php
/**
 * Botão de logout com proteção CSRF (POST). Reutilizável pela sidebar e pelo admin.
 *
 * @var string $logoutClasse Classe(s) CSS do botão (opcional).
 * @var string $logoutRotulo Texto do botão (opcional).
 */
require_once __DIR__ . '/../../../backend/core/Csrf.php';
require_once __DIR__ . '/../../../backend/core/Url.php';

$logoutClasse = $logoutClasse ?? 'botao botao-secundario';
$logoutRotulo = $logoutRotulo ?? 'Sair';
?>
<form method="post" action="<?= Url::escapar('/app/logout/') ?>" class="form-sair">
    <?= Csrf::campoHtml() ?>
    <button type="submit" class="<?= htmlspecialchars($logoutClasse, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($logoutRotulo, ENT_QUOTES, 'UTF-8') ?></button>
</form>
