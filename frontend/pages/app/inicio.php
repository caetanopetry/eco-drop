<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/core/Auth.php';
require_once __DIR__ . '/../../../backend/core/Url.php';
require_once __DIR__ . '/../../../backend/repositories/PontosRepository.php';

Auth::iniciarSessao();
$usuario = Auth::exigirAutenticacaoPagina(Url::para('/app/login/'));

if ($usuario['papel'] === 'admin') {
    header('Location: ' . Url::para('/admin/dashboard/'));
    exit;
}

$saldo = (new PontosRepository())->saldoDoUsuario((int) $usuario['id']);
$paginaAtual = 'inicio';
$primeiroNome = explode(' ', $usuario['nome'])[0];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Início — EcoDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= Url::escapar('/frontend/assets/css/app.css') ?>">
</head>
<body>
    <?php include __DIR__ . '/../../components/navbar/navbar-app.php'; ?>

    <main class="conteudo-app">
        <div class="container">
            <div class="saudacao-app">
                <h1>Olá, <?= htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="texto-suave">Acompanhe seus pontos e prepare-se para o próximo descarte.</p>
            </div>

            <section class="bloco-saldo" aria-label="Seu saldo de pontos">
                <p class="rotulo">Seu saldo</p>
                <p class="valor"><?= number_format($saldo, 0, ',', '.') ?><small>pontos</small></p>
            </section>

            <h2>O que vem a seguir</h2>
            <ul class="lista-proximos-passos">
                <li>
                    <span class="marcador">→</span>
                    <div>
                        <strong>Localizar uma estação</strong>
                        <span class="texto-suave">Disponível em uma próxima fase.</span>
                    </div>
                </li>
                <li>
                    <span class="marcador">→</span>
                    <div>
                        <strong>Ver histórico de descartes</strong>
                        <span class="texto-suave">Disponível em uma próxima fase.</span>
                    </div>
                </li>
                <li>
                    <span class="marcador">→</span>
                    <div>
                        <strong>Ranking mensal</strong>
                        <span class="texto-suave">Disponível em uma próxima fase.</span>
                    </div>
                </li>
                <li>
                    <span class="marcador">→</span>
                    <div>
                        <strong>Trocar pontos por recompensas</strong>
                        <span class="texto-suave">Disponível em uma próxima fase.</span>
                    </div>
                </li>
            </ul>
        </div>
    </main>

    <footer class="rodape-ecodrop">
        <div class="container">EcoDrop — plataforma de coleta seletiva incentivada.</div>
    </footer>
</body>
</html>
