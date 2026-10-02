<?php

declare(strict_types=1);

require_once __DIR__ . '/Env.php';

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

/**
 * Configuração centralizada do Google OAuth 2.0.
 */
final class GoogleOAuth
{
    private function __construct()
    {
    }

    public static function configuracao(): array
    {
        Env::carregar();

        $scopes = (string) ($_ENV['GOOGLE_SCOPES'] ?? getenv('GOOGLE_SCOPES') ?? 'openid email profile');
        $escopos = array_values(array_filter(
            array_map('trim', preg_split('/\s+/', $scopes) ?: []),
            static fn (string $scope): bool => $scope !== ''
        ));

        if ($escopos === []) {
            $escopos = ['openid', 'email', 'profile'];
        }

        return [
            'client_id' => (string) ($_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? ''),
            'client_secret' => (string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? ''),
            'redirect_uri' => (string) ($_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?? ''),
            'scopes' => $escopos,
        ];
    }

    public static function cliente(): Google\Client
    {
        $config = self::configuracao();
        $cliente = new Google\Client();

        $cliente->setClientId($config['client_id']);
        $cliente->setClientSecret($config['client_secret']);
        $cliente->setRedirectUri($config['redirect_uri']);
        $cliente->setAccessType('offline');
        $cliente->setPrompt('select_account');
        $cliente->setIncludeGrantedScopes(false);
        $cliente->addScope($config['scopes']);

        return $cliente;
    }

    public static function iniciarFluxo(): string
    {
        if (!isset($_SESSION)) {
            throw new RuntimeException('A sessão deve ser iniciada antes do início do fluxo do Google OAuth.');
        }

        $cliente = self::cliente();
        $estado = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $estado;
        $cliente->setState($estado);

        return $cliente->createAuthUrl();
    }
}
