<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Env.php';
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

        if (strtolower($host) !== 'smtp-relay.brevo.com' || $porta !== 587) {
            throw new EmailDeliveryException('A configuração SMTP do Brevo deve usar o host e a porta 587.');
        }

        if (filter_var($remetente, FILTER_VALIDATE_EMAIL) === false) {
            throw new EmailDeliveryException('O remetente SMTP configurado não é um e-mail válido.');
        }

        $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $linkSeguro = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->SMTPAuth = true;
            $mailer->Username = $usuario;
            $mailer->Password = $senha;
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port = $porta;
            $mailer->Timeout = 15;
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->setFrom($remetente, $nomeRemetente);
            $mailer->addAddress($destinatario, $nome);
            $mailer->isHTML(true);
            $mailer->Subject = 'Redefinição de senha — EcoDrop';
            $mailer->Body = '<!doctype html><html lang="pt-BR"><body>'
                . '<p>Olá, ' . $nomeSeguro . '.</p>'
                . '<p>Recebemos uma solicitação para redefinir a senha da sua conta EcoDrop.</p>'
                . '<p><a href="' . $linkSeguro . '">Redefinir minha senha</a></p>'
                . '<p>Este link expira em 60 minutos e só pode ser usado uma vez.</p>'
                . '<p>Se você não solicitou a redefinição, ignore este e-mail. Sua senha atual não será enviada nem alterada.</p>'
                . '</body></html>';
            $mailer->AltBody = "Olá, {$nome}.\n\n"
                . "Para redefinir sua senha EcoDrop, acesse:\n{$link}\n\n"
                . "O link expira em 60 minutos e só pode ser usado uma vez. "
                . "Se você não solicitou a redefinição, ignore este e-mail.";
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
