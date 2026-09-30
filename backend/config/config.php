<?php

declare(strict_types=1);

/**
 * Configuração central do EcoDrop.
 *
 * Este arquivo é o EXEMPLO versionado no repositório.
 * Copie para "config.php" (mesma pasta) e ajuste os valores locais.
 * "config.php" está no .gitignore e nunca deve conter credenciais reais de produção.
 */

return [
    'app' => [
        // URL base da aplicação no XAMPP. Ajuste conforme a pasta do projeto.
        'url_base' => 'http://localhost/EcoDrop',
        'timezone' => 'America/Sao_Paulo',
    ],

    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3307',
        'database' => 'ecodrop',
        'usuario' => 'root',
        'senha' => '',
        'charset' => 'utf8mb4',
    ],

    'sessao' => [
        'nome_cookie' => 'ecodrop_session',
        // Tempo de vida do cookie de sessão, em segundos (8 horas).
        'tempo_vida' => 60 * 60 * 8,
    ],

];
