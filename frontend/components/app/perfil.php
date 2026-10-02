<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/repositories/UsuarioRepository.php';

// Consulta o cadastro a cada página para refletir a foto sem exigir novo login.
$perfilCabecalho = (new UsuarioRepository())->buscarPerfil((int) $usuario['id']);
$nomeCabecalho = $perfilCabecalho['nome'] ?? $usuario['nome'];
$fotoCabecalho = ($perfilCabecalho['foto_personalizada'] ?? null)
    ?: ($perfilCabecalho['foto_google'] ?? null);
$inicialCabecalho = mb_strtoupper(mb_substr(trim($nomeCabecalho), 0, 1));
?>
<a class="perfil-pill" href="<?= Url::escapar('/app/conta/') ?>" aria-label="Abrir minha conta">
    <span class="perfil-avatar" aria-hidden="true">
        <?= htmlspecialchars($inicialCabecalho, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($fotoCabecalho): ?>
            <img src="<?= htmlspecialchars($fotoCabecalho, ENT_QUOTES, 'UTF-8') ?>" alt="" width="30" height="30" referrerpolicy="no-referrer">
        <?php endif; ?>
    </span>
    <strong><?= htmlspecialchars($nomeCabecalho, ENT_QUOTES, 'UTF-8') ?></strong>
</a>
