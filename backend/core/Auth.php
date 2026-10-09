<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/../repositories/SessaoAcessoRepository.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

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
        $acesso = (new UsuarioRepository())->buscarAcesso((int) $usuario['id']);
        if ($acesso === null || $acesso['status'] !== 'ativo') {
            throw new RuntimeException('A conta não está disponível para autenticação.');
        }
        $usuario = $acesso;
        if (isset($_SESSION['usuario'])) {
            self::registrarAtividade('encerrar');
        }
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'papel' => $usuario['papel'],
        ];
        $_SESSION['_auth_senha'] = hash('sha256', $acesso['senha_hash']);
        self::registrarAtividade('registrar');
    }

    public static function encerrarSessao(): void
    {
        self::iniciarSessao();
        if (isset($_SESSION['usuario'])) {
            self::registrarAtividade('encerrar');
        }
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
        if (isset($_SESSION['usuario'])) {
            $acesso = (new UsuarioRepository())->buscarAcesso((int) $_SESSION['usuario']['id']);
            if ($acesso === null || $acesso['status'] !== 'ativo'
                || (isset($_SESSION['_auth_senha']) && !hash_equals($_SESSION['_auth_senha'], hash('sha256', $acesso['senha_hash'])))) {
                self::encerrarSessao();
                return null;
            }
            // Migra sessões anteriores e reflete alterações de perfil feitas no banco.
            $_SESSION['_auth_senha'] = hash('sha256', $acesso['senha_hash']);
            $_SESSION['usuario'] = [
                'id' => (int) $acesso['id'], 'nome' => $acesso['nome'],
                'email' => $acesso['email'], 'papel' => $acesso['papel'],
            ];
            self::registrarAtividade('atualizar');
        }
        return $_SESSION['usuario'] ?? null;
    }

    /** O histórico de acessos não deve impedir login/logout durante uma migração. */
    private static function registrarAtividade(string $acao): void
    {
        try {
            $repo = new SessaoAcessoRepository();
            if ($acao === 'encerrar') {
                $repo->encerrar(session_id());
            } elseif ($acao === 'registrar') {
                $repo->registrar((int) $_SESSION['usuario']['id'], session_id());
            } else {
                $repo->atualizar((int) $_SESSION['usuario']['id'], session_id());
            }
        } catch (Throwable $e) {
            error_log('[EcoDrop] Falha ao registrar atividade de acesso (' . get_class($e) . '). Verifique a migração sessoes_acesso.');
        }
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
