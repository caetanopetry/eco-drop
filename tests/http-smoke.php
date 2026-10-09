<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../backend/core/Auth.php';
require_once __DIR__ . '/../backend/core/Url.php';

$base = rtrim((string) Config::get('app.url_base'), '/');
if (!in_array(parse_url($base, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Este teste HTTP só pode usar o servidor local.');
}
$verificacoes = 0;
function requisitar(string $url, ?string $cookie = null, ?array $json = null, bool $formulario = false): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15]);
    if ($cookie !== null) curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if ($json !== null) curl_setopt_array($curl, [CURLOPT_POST=>true, CURLOPT_HTTPHEADER=>['Content-Type: '.($formulario ? 'application/x-www-form-urlencoded' : 'application/json')], CURLOPT_POSTFIELDS=>$formulario ? http_build_query($json) : json_encode($json)]);
    $body = curl_exec($curl);
    if ($body === false) throw new RuntimeException('Falha ao acessar o servidor local.');
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$status,$body];
}
function status(string $rota, int $esperado, ?string $cookie = null, ?array $json = null, bool $formulario = false): string
{
    global $base, $verificacoes;
    [$codigo,$body] = requisitar($base.$rota, $cookie, $json, $formulario);
    if ($codigo !== $esperado) throw new RuntimeException("$rota retornou $codigo, esperado $esperado");
    $verificacoes++;
    return $body;
}
function pagina(string $rota, ?string $cookie = null): void
{
    global $base;
    $html = status($rota, 200, $cookie);
    if (substr_count($html, '/frontend/assets/js/theme.js') !== 1
        || strpos($html, '/frontend/assets/js/theme.js') > strpos($html, 'rel="stylesheet"')
        || !str_contains($html, 'data-theme-toggle') || !str_contains($html, 'eco-marca__escura')) {
        throw new RuntimeException('Integração do tema incompleta: ' . $rota);
    }
    preg_match_all('~(?:href|src)="(' . preg_quote(Url::para('/frontend/assets/'), '~') . '(?:css|js)/[^"<>]+)"~', $html, $assets);
    $origem = parse_url($base);
    $host = $origem['scheme'].'://'.$origem['host'].(isset($origem['port']) ? ':'.$origem['port'] : '');
    foreach (array_unique($assets[1]) as $asset) {
        if (requisitar($host.html_entity_decode($asset))[0] !== 200) throw new RuntimeException('Recurso inacessível em ' . $rota);
    }
}
Auth::iniciarSessao();
$_SESSION = [];
session_write_close();
foreach (['/', '/sobre/', '/contato/', '/perguntas-frequentes/', '/termos-de-uso/', '/politica-de-privacidade/', '/app/login/', '/app/cadastro/', '/admin/login/', '/app/esqueci-senha/', '/app/redefinir-senha/', '/station/'] as $rota) pagina($rota);
foreach (['/.env','/.git/HEAD','/backend/database/schema.sql','/backend/database/schema%20copy.sql','/backend/core/Auth.php','/backend/services/EmailService.php','/vendor/autoload.php','/composer.json','/tests/integration.php'] as $rota) status($rota,403);
foreach (['/app/inicio/','/admin/dashboard/','/admin/usuarios/','/app/cadastro/google/'] as $rota) status($rota,302);
status('/backend/api/auth/me.php',401);
status('/backend/api/auth/login.php',405);
foreach (['login','register','google-completar'] as $endpoint) status('/backend/api/auth/'.$endpoint.'.php',403,null,['csrf_token'=>'invalido']);
status('/backend/api/status.php',200);
status('/station/',403,null,['csrf_token'=>['invalido']],true);

$pdo = Database::conexao();
foreach (['usuario'=>['/app/inicio/','/app/saques/','/app/extrato/','/app/conta/'], 'admin'=>['/admin/dashboard/','/admin/usuarios/']] as $papel=>$rotas) {
    $stmt = $pdo->prepare("SELECT id,nome,email,papel FROM usuarios WHERE papel=? AND status='ativo' LIMIT 1"); $stmt->execute([$papel]);
    $usuario = $stmt->fetch();
    if (!$usuario) throw new RuntimeException('É necessária uma conta local ativa para testar o perfil ' . $papel);
    session_id(bin2hex(random_bytes(16))); session_start();
    $_SESSION['usuario'] = $usuario;
    $cookie = session_name().'='.session_id();
    session_write_close();
    try {
        foreach ($rotas as $rota) pagina($rota,$cookie);
        status('/backend/api/auth/me.php',200,$cookie);
        status($papel === 'usuario' ? '/admin/usuarios/' : '/app/inicio/',302,$cookie);
        if ($papel === 'usuario') {
            status('/app/conta/',403,$cookie,['csrf_token'=>'invalido']);
            status('/app/saques/',403,$cookie,['csrf_token'=>['invalido']],true);
        }
    } finally {
        session_start(); $_SESSION=[]; session_destroy();
    }
}
echo "$verificacoes verificações HTTP aprovadas, incluindo 18 páginas, acesso por perfil e bloqueio de arquivos internos. Nenhum e-mail enviado.\n";
