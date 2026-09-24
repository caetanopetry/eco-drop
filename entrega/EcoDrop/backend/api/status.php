<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'online',
    'aplicacao' => 'EcoDrop',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
