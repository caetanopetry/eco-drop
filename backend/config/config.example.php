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

    'contato' => [
        // E-mail público de atendimento. Vazio mantém o formulário indisponível.
        'email' => '',
        // Número completo com código do país e DDD, somente dígitos.
        'whatsapp' => '',
        // Nome do perfil, sem URL. O @ é opcional.
        'instagram' => '',
    ],

    'sessao' => [
        'nome_cookie' => 'ecodrop_session',
        // Tempo de vida do cookie de sessão, em segundos (8 horas).
        'tempo_vida' => 60 * 60 * 8,
    ],

];
