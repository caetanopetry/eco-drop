<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';

/**
 * Sessão e controle de acesso por perfil (AT01 - usuário, AT02 - administrador).
 *
 * - Sessão HttpOnly, SameSite=Lax (proteção básica contra CSRF/XSS lendo o cookie).
 * - Regeneração de ID de sessão no login (proteção contra fixação de sessão).
 * - Nenhuma rota administrativa confia em dado vindo do cliente: o perfil é lido
 *   exclusivamente da sessão do servidor (RNF004, RNF015).
 */
final class Auth
{
    private function __construct()
    {
    }

    public static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => (int) Config::get('sessao.tempo_vida', 28800),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name((string) Config::get('sessao.nome_cookie', 'ecodrop_session'));
        session_start();
    }

    public static function autenticar(array $usuario): void
    {
        self::iniciarSessao();
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'papel' => $usuario['papel'],
        ];
    }

    public static function encerrarSessao(): void
    {
        self::iniciarSessao();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametros['path'],
                $parametros['domain'],
                $parametros['secure'],
                $parametros['httponly']
            );
        }

        session_destroy();
    }

    public static function usuarioLogado(): ?array
    {
        self::iniciarSessao();
        return $_SESSION['usuario'] ?? null;
    }

    public static function estaAutenticado(): bool
    {
        return self::usuarioLogado() !== null;
    }

    public static function possuiPapel(string $papel): bool
    {
        $usuario = self::usuarioLogado();
        return $usuario !== null && $usuario['papel'] === $papel;
    }

    /**
     * Usado por endpoints da API: interrompe a requisição com erro 401 se não autenticado.
     */
    public static function exigirAutenticacaoApi(): array
    {
        $usuario = self::usuarioLogado();

        if ($usuario === null) {
            Response::erro('Sessão não autenticada.', 401);
        }

        return $usuario;
    }

    public static function exigirPapelApi(string $papel): array
    {
        $usuario = self::exigirAutenticacaoApi();

        if ($usuario['papel'] !== $papel) {
            Response::erro('Acesso não autorizado para este perfil.', 403);
        }

        return $usuario;
    }

    /**
     * Usado por páginas HTML: redireciona para o login apropriado se não autenticado.
     */
    public static function exigirAutenticacaoPagina(string $urlLogin): array
    {
        $usuario = self::usuarioLogado();

        if ($usuario === null) {
            header('Location: ' . $urlLogin);
            exit;
        }

        return $usuario;
    }

    public static function exigirPapelPagina(string $papel, string $urlLogin): array
    {
        $usuario = self::exigirAutenticacaoPagina($urlLogin);

        if ($usuario['papel'] !== $papel) {
            header('Location: ' . $urlLogin);
            exit;
        }

        return $usuario;
    }
}
