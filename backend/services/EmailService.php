<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/EmailTemplate.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class EmailDeliveryException extends RuntimeException
{
}

final class EmailService
{
    public function enviarLinkRecuperacao(string $destinatario, string $nome, string $link): void
    {
        Env::carregar();

        $host = $this->variavelObrigatoria('SMTP_HOST');
        $porta = filter_var($this->variavelObrigatoria('SMTP_PORT'), FILTER_VALIDATE_INT);
        $usuario = $this->variavelObrigatoria('SMTP_USERNAME');
        $senha = $this->variavelObrigatoria('SMTP_PASSWORD');
        $remetente = $this->variavelObrigatoria('SMTP_FROM');
        $nomeRemetente = $this->variavelObrigatoria('SMTP_FROM_NAME');

        if ($porta === false || $porta < 1 || $porta > 65535) {
            throw new EmailDeliveryException('A porta SMTP configurada é inválida.');
        }

        if (filter_var($remetente, FILTER_VALIDATE_EMAIL) === false) {
            throw new EmailDeliveryException('O remetente SMTP configurado não é um e-mail válido.');
        }

        $logo = dirname(__DIR__, 2) . '/frontend/assets/img/logos/logo-email.png';
        $logoCid = is_file($logo) ? 'logo-ecodrop' : null;
        $conteudo = EmailTemplate::recuperacaoSenha(
            $nome,
            $link,
            (string) Config::get('app.url_base', ''),
            $logoCid
        );

        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->SMTPAuth = true;
            $mailer->Username = $usuario;
            $mailer->Password = $senha;
            // Porta 465 usa TLS implícito; as demais usam STARTTLS (ex.: 587).
            $mailer->SMTPSecure = $porta === 465
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port = $porta;
            $mailer->Timeout = 15;
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->setFrom($remetente, $nomeRemetente);
            $mailer->addAddress($destinatario, $nome);
            $mailer->isHTML(true);
            if ($logoCid !== null) {
                $mailer->addEmbeddedImage($logo, $logoCid, 'logo-ecodrop.png', 'base64', 'image/png');
            }
            $mailer->Subject = $conteudo['assunto'];
            $mailer->Body = $conteudo['html'];
            $mailer->AltBody = $conteudo['texto'];
            $mailer->send();
            error_log('[EcoDrop] E-mail de recuperação submetido ao SMTP para domínio: ' . $this->dominioEmail($destinatario) . '.');
        } catch (PHPMailerException $e) {
            throw new EmailDeliveryException('Falha no envio do e-mail de recuperação.', 0, $e);
        }
    }

    private function dominioEmail(string $email): string
    {
        $posicaoArroba = strrpos($email, '@');
        if ($posicaoArroba === false) {
            return 'desconhecido';
        }

        return mb_strtolower(substr($email, $posicaoArroba + 1));
    }

    private function variavelObrigatoria(string $nome): string
    {
        $valor = $_ENV[$nome] ?? getenv($nome);
        if (!is_string($valor) || trim($valor) === '') {
            throw new EmailDeliveryException('Variável SMTP obrigatória ausente: ' . $nome . '.');
        }

        return trim($valor);
    }
}
