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
        // URL base da aplicação no ambiente local de teste.
        'url_base' => 'http://localhost:9999/EcoDrop',
        'timezone' => 'America/Sao_Paulo',
    ],

    'sessao' => [
        'nome_cookie' => 'ecodrop_session',
        // Tempo de vida do cookie de sessão, em segundos (8 horas).
        'tempo_vida' => 60 * 60 * 8,
    ],

];
