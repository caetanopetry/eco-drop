<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/UsuarioRepository.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Url.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/EmailService.php';

final class PasswordRecoveryService
{
    private const TEMPO_EXPIRACAO_MINUTOS = 15;

    public function __construct(
        private readonly UsuarioRepository $usuarios = new UsuarioRepository(),
        private readonly EmailService $email = new EmailService()
    ) {
    }

    public function solicitar(string $email): void
    {
        $email = Validator::emailNormalizado($email);
        if (!Validator::email($email) || mb_strlen($email) > 190) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }

        $usuario = $this->usuarios->buscarPorEmail($email);
        if ($usuario === null || $usuario['status'] !== 'ativo') {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiraEm = gmdate('Y-m-d H:i:s', time() + self::TEMPO_EXPIRACAO_MINUTOS * 60);

        $this->usuarios->criarRecuperacao((int) $usuario['id'], $tokenHash, $expiraEm);
        try {
            $this->email->enviarLinkRecuperacao(
                $usuario['email'],
                $usuario['nome'],
                $this->linkRecuperacao($token)
            );
        } catch (EmailDeliveryException $e) {
            $this->usuarios->invalidarRecuperacao($tokenHash);
            throw $e;
        }
    }

    public function tokenValido(string $token): bool
    {
        if (preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
            return false;
        }

        return $this->usuarios->buscarRecuperacaoValida(hash('sha256', $token));
    }

    public function redefinirSenha(string $token, string $senha): bool
    {
        if (
            preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1
            || !Validator::senhaForteMinima($senha)
        ) {
            return false;
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        if (!is_string($senhaHash)) {
            throw new RuntimeException('Não foi possível proteger a nova senha.');
        }

        return $this->usuarios->redefinirSenhaComToken(hash('sha256', $token), $senhaHash);
    }

    private function linkRecuperacao(string $token): string
    {
        $base = (string) Config::get('app.url_base', '');
        $partes = parse_url($base);
        if (
            !is_array($partes)
            || !in_array($partes['scheme'] ?? '', ['http', 'https'], true)
            || !isset($partes['host'])
        ) {
            throw new RuntimeException('A URL base da aplicação não está configurada corretamente.');
        }

        $origem = $partes['scheme'] . '://' . $partes['host'];
        if (isset($partes['port'])) {
            $origem .= ':' . $partes['port'];
        }

        return $origem . Url::para('/app/redefinir-senha/')
            . '?token=' . rawurlencode($token);
    }
}
