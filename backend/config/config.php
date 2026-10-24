<?php

declare(strict_types=1);

/**
 * Configuração central do EcoDrop.
 *
 * Este arquivo é versionado e contém configurações gerais sem credenciais.
 * As credenciais do banco são carregadas do .env por Config.php.
 * Nunca inclua segredos neste arquivo.
 */

return [
    'app' => [
        // URL base da aplicação no ambiente local de teste.
        // Deve corresponder à porta do Apache e ao nome real da pasta em htdocs.
        'url_base' => 'http://localhost:9999/EcoDrop',
        'timezone' => 'America/Sao_Paulo',
    ],

    'sessao' => [
        'nome_cookie' => 'ecodrop_session',
        // Tempo de vida do cookie de sessão, em segundos (8 horas).
        'tempo_vida' => 60 * 60 * 8,
    ],

];
