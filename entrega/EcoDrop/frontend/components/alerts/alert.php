<?php
/**
 * Área reservada para alertas de feedback inseridos via JavaScript (auth.js).
 * Mantido como componente próprio para reforço de acessibilidade (aria-live).
 *
 * @var string $id Identificador único da área (opcional).
 */
$id = $id ?? 'feedback-formulario';
?>
<div id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" data-area-feedback aria-live="polite"></div>
