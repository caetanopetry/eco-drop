<?php

declare(strict_types=1);

/**
 * Monta o conteúdo HTML e texto puro dos e-mails transacionais do EcoDrop.
 *
 * O layout usa tabelas e estilos inline por compatibilidade com clientes de
 * e-mail (Gmail, Outlook, Apple Mail). O logo é enviado embutido via CID.
 */
final class EmailTemplate
{
    public const EXPIRACAO_MINUTOS = 15;

    private function __construct()
    {
    }

    /**
     * @return array{assunto: string, html: string, texto: string}
     */
    public static function recuperacaoSenha(
        string $nome,
        string $link,
        string $urlBase,
        ?string $logoCid = null
    ): array {
        $nome = trim($nome);

        $partesNome = preg_split('/\s+/u', $nome) ?: [];
        $primeiroNome = $partesNome[0] ?? '';

        $saudacao = $primeiroNome !== ''
            ? 'Olá, ' . self::escapar($primeiroNome) . ','
            : 'Olá,';

        $linkSeguro = self::escapar($link);
        // Exibe o endereço sem o esquema (http:// ou https://) para leitura mais limpa.
        $linkExibicao = self::escapar((string) preg_replace('#^https?://#i', '', $link));
        $baseSegura = self::escapar(rtrim($urlBase, '/'));
        $privacidadeSegura = self::escapar(rtrim($urlBase, '/') . '/politica-de-privacidade/');

        $logo = $logoCid !== null && $logoCid !== ''
            ? '<img src="cid:' . self::escapar($logoCid) . '" width="210" height="70" alt="EcoDrop"'
                . ' style="display:block;width:210px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;">'
            : '<div style="font-family:Georgia,\'Times New Roman\',serif;font-size:28px;font-weight:bold;'
                . 'letter-spacing:-.5px;color:#163d31;">EcoDrop<span style="color:#65b32e;">.</span></div>';

        $expira = self::EXPIRACAO_MINUTOS;

        $html = <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title>Redefinição de senha — EcoDrop</title>
</head>
<body style="margin:0;padding:0;background-color:#eef1ec;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">Use este link para criar uma nova senha na sua conta EcoDrop. Ele expira em {$expira} minutos.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef1ec;">
<tr>
<td align="center" style="padding:32px 16px 24px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">
    <tr>
      <td align="center" style="padding:4px 0 26px;">{$logo}</td>
    </tr>
    <tr>
      <td style="background-color:#ffffff;border:1px solid #e1e7e0;border-radius:12px;overflow:hidden;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td align="center" style="padding:40px 44px 30px;border-bottom:1px solid #edf0ec;">
              <p style="margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;letter-spacing:1.6px;text-transform:uppercase;color:#2f6b4f;">Recuperação de acesso</p>
              <h1 style="margin:0 0 12px;font-family:Georgia,'Times New Roman',serif;font-size:29px;line-height:1.25;font-weight:normal;color:#163d31;">Vamos criar uma nova senha?</h1>
              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#5b665e;">Recebemos um pedido para redefinir a senha da sua conta EcoDrop.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 44px 40px;">
              <p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#17211b;">{$saudacao}</p>
              <p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.65;color:#4a554d;">Para criar uma nova senha, basta clicar no botão abaixo. Por segurança, este link expira em <strong style="color:#17211b;">{$expira} minutos</strong> e só pode ser usado uma vez.</p>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:28px auto 20px;">
                <tr>
                  <td align="center" bgcolor="#2f6b4f" style="border-radius:8px;">
                    <a href="{$linkSeguro}" target="_blank" rel="noopener" style="display:inline-block;padding:15px 40px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;line-height:1.2;color:#ffffff;text-decoration:none;border-radius:8px;">Criar nova senha</a>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#7b857d;text-align:center;">Se o botão não funcionar, copie e cole este endereço no navegador:</p>
              <p style="margin:0 0 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#2f6b4f;text-align:center;word-break:break-all;"><a href="{$linkSeguro}" target="_blank" rel="noopener" style="color:#2f6b4f;text-decoration:underline;">{$linkExibicao}</a></p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:16px 18px;background-color:#f5f8f4;border-left:3px solid #2f6b4f;border-radius:4px;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.65;color:#5b665e;"><strong style="color:#17211b;">Não foi você?</strong> Se você não solicitou esta alteração, nenhuma ação é necessária. Sua senha atual continua válida e nenhuma mudança será feita.</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td align="center" style="padding:24px 24px 8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.8;color:#7b857d;">
        <p style="margin:0 0 6px;">
          <a href="{$baseSegura}" target="_blank" rel="noopener" style="color:#2f6b4f;text-decoration:none;font-weight:bold;">Acessar o EcoDrop</a>
          <span style="color:#c3ccc3;">&nbsp;·&nbsp;</span>
          <a href="{$privacidadeSegura}" target="_blank" rel="noopener" style="color:#2f6b4f;text-decoration:none;font-weight:bold;">Política de privacidade</a>
        </p>
        <p style="margin:0 0 6px;color:#6f7a72;">EcoDrop · Reciclagem que recompensa</p>
        <p style="margin:0;color:#737d75;">Este é um e-mail automático. Por favor, não responda.</p>
      </td>
    </tr>
  </table>
</td>
</tr>
</table>
</body>
</html>
HTML;

        $texto = "Olá" . ($primeiroNome !== '' ? ', ' . $primeiroNome : '') . ".\n\n"
            . "Recebemos um pedido para redefinir a senha da sua conta EcoDrop.\n\n"
            . "Para criar uma nova senha, acesse:\n{$link}\n\n"
            . "Este link expira em {$expira} minutos e só pode ser usado uma vez.\n\n"
            . "Se você não solicitou esta alteração, nenhuma ação é necessária. "
            . "Sua senha atual continua válida.\n\n"
            . "---\n"
            . "EcoDrop · Reciclagem que recompensa\n"
            . "Acessar o EcoDrop: " . rtrim($urlBase, '/') . "\n"
            . "Política de privacidade: " . rtrim($urlBase, '/') . "/politica-de-privacidade/\n"
            . "Este é um e-mail automático. Por favor, não responda.\n";

        return [
            'assunto' => 'Redefinição de senha — EcoDrop',
            'html' => $html,
            'texto' => $texto,
        ];
    }

    private static function escapar(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
