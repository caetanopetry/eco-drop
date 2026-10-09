<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !preg_match('/^ecodrop_test_[a-f0-9]{12}$/', $argv[1] ?? '')) {
    exit(1);
}
// Sobrescreve somente o nome do banco descartável, antes de carregar a configuração.
$_ENV['DB_NAME'] = $argv[1];
require_once __DIR__ . '/../backend/repositories/EcoDropRepository.php';
try {
    echo json_encode((new EcoDropRepository())->resgatarRecompensa((int) $argv[2], (int) $argv[3]), JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e));
    exit(1);
}
