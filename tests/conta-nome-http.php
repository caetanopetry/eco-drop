<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../backend/core/Auth.php';
require_once __DIR__ . '/../backend/core/Csrf.php';
require_once __DIR__ . '/../backend/services/AuthService.php';

// Servidor e banco exclusivos: nenhuma conta do ambiente local é alterada.
$raiz = dirname(__DIR__);
$banco = 'ecodrop_test_' . bin2hex(random_bytes(6));
$configProperty = new ReflectionProperty(Config::class, 'dados');
Config::get('database');
$configOriginal = $configProperty->getValue();
$db = $configOriginal['database'];
$servidor = new PDO("mysql:host={$db['host']};port={$db['port']};charset=utf8mb4", $db['usuario'], $db['senha'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$processo = null;
$router = tempnam(sys_get_temp_dir(), 'ecodrop_nome_');
$log = tempnam(sys_get_temp_dir(), 'ecodrop_nome_log_');
$fotoTeste = tempnam(sys_get_temp_dir(), 'ecodrop_foto_');
$criado = false;
$checagens = 0;
function conferirNome(bool $valor, string $mensagem): void {
    global $checagens;
    if (!$valor) throw new RuntimeException($mensagem);
    $checagens++;
}
function requisicaoNome(string $base, ?string $cookie, ?array $post = null, string $rota = '/app/conta/', bool $multipart = false): array {
    $curl = curl_init($base . $rota);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
    if ($cookie !== null) curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $multipart ? $post : http_build_query($post)]);
    $html = curl_exec($curl);
    if ($html === false) throw new RuntimeException('Servidor de teste indisponível: ' . curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$status, $html];
}
try {
    $servidor->exec("CREATE DATABASE `$banco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $criado = true;
    $config = $configOriginal;
    $config['database']['database'] = $banco;
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if (!$socket) throw new RuntimeException('Não foi possível reservar porta de teste.');
    $endereco = stream_socket_get_name($socket, false);
    fclose($socket);
    $base = 'http://' . $endereco;
    $config['app']['url_base'] = $base;
    $config['sessao']['nome_cookie'] = 'ecodrop_nome_test';
    $configProperty->setValue(null, $config);
    $pdo = Database::conexao();
    $schema = file_get_contents($raiz . '/backend/database/schema.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS ecodrop\s+.*?;/s', '', $schema);
    $pdo->exec(str_replace('USE ecodrop;', '', $schema));
    $repo = new UsuarioRepository();
    $id = $repo->criar('Nome Original', 'nome@example.test', '12345678909', password_hash('SenhaTeste@123', PASSWORD_DEFAULT));
    $outroId = $repo->criar('Outra Pessoa', 'outra@example.test', '98765432100', password_hash('OutraSenha@123', PASSWORD_DEFAULT));
    $preparar = $pdo->prepare("UPDATE usuarios SET google_sub='google-sub-teste', foto_google='https://example.test/avatar.png', atualizado_em='2000-01-01 00:00:00' WHERE id=?");
    $preparar->execute([$id]);
    $lerRegistro = static function (int $usuarioId) use ($pdo): array {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id=?');
        $stmt->execute([$usuarioId]);
        return $stmt->fetch();
    };
    $registroOriginal = $lerRegistro($id);
    $outroOriginal = $lerRegistro($outroId);
    $original = $repo->buscarPorEmail('nome@example.test');
    Auth::autenticar(['id' => $id]);
    $csrf = Csrf::token();
    $cookie = session_name() . '=' . session_id();
    $sessaoId = session_id();
    session_write_close();
    // Configuração herdada do projeto; somente banco, URL e cookie são substituídos.
    $codigo = '<?php require_once ' . var_export($raiz . '/backend/core/Config.php', true) . ';'
        . 'Config::get("database"); $p = new ReflectionProperty(Config::class, "dados"); $c = $p->getValue();'
        . '$c["database"]["database"] = ' . var_export($banco, true) . ';'
        . '$c["app"]["url_base"] = ' . var_export($base, true) . ';'
        . '$c["sessao"]["nome_cookie"] = "ecodrop_nome_test"; $p->setValue(null, $c);'
        . '$rota = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);'
        . 'if ($rota === "/app/conta/") { require ' . var_export($raiz . '/frontend/pages/app/conta.php', true) . '; }'
        . 'elseif ($rota === "/app/inicio/") { require ' . var_export($raiz . '/frontend/pages/app/inicio.php', true) . '; }'
        . 'else { http_response_code(404); }';
    file_put_contents($router, $codigo);
    $processo = proc_open([PHP_BINARY, '-S', $endereco, $router], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $raiz);
    if (!is_resource($processo)) throw new RuntimeException('Não foi possível iniciar servidor de teste.');
    fclose($pipes[0]);
    for ($i = 0; $i < 50; $i++) {
        $pronto = @stream_socket_client('tcp://' . $endereco, $errno, $errstr, .1);
        if ($pronto) { fclose($pronto); break; }
        usleep(100000);
    }
    conferirNome(requisicaoNome($base, null)[0] === 302, 'Acesso anônimo permitido.');
    [$status, $html] = requisicaoNome($base, $cookie);
    conferirNome($status === 200 && str_contains($html, 'Alterar nome'), 'Editor indisponível.');
    $post = ['acao' => 'alterar_nome', 'csrf_token' => $csrf, 'nome' => '  João  D’Ávila  '];
    conferirNome(requisicaoNome($base, $cookie, array_replace($post, ['csrf_token' => 'invalido']))[0] === 403, 'CSRF não bloqueado.');
    foreach (['', " \u{00a0} ", str_repeat('Á', 151), ['nome inesperado']] as $invalido) {
        [$status, $html] = requisicaoNome($base, $cookie, array_replace($post, ['nome' => $invalido]));
        conferirNome($status === 422 && str_contains($html, 'class="conta-nome-editor" open'), 'Nome inválido aceito ou editor fechado.');
    }
    conferirNome($repo->buscarPerfil($id)['nome'] === 'Nome Original', 'Requisição inválida alterou o nome.');
    $post['usuario_id'] = $outroId;
    $post['email'] = 'invadido@example.test';
    $post['senha'] = 'SenhaIgnorada';
    conferirNome(requisicaoNome($base, $cookie, $post)[0] === 303, 'Salvamento não redirecionou.');
    conferirNome($repo->buscarPerfil($id)['nome'] === 'João D’Ávila', 'Nome não normalizado/persistido.');
    conferirNome($repo->buscarPerfil($outroId)['nome'] === 'Outra Pessoa', 'Alterou outra conta.');
    $atual = $repo->buscarPorEmail('nome@example.test');
    conferirNome($atual['email'] === $original['email'] && $atual['senha_hash'] === $original['senha_hash'], 'Alterou credenciais.');
    $registroAtual = $lerRegistro($id);
    $outrosCampos = static fn(array $registro): array => array_diff_key($registro, ['nome' => true, 'atualizado_em' => true]);
    conferirNome($outrosCampos($registroAtual) === $outrosCampos($registroOriginal), 'Alterou CPF, vínculo Google, foto ou outros campos.');
    conferirNome($registroAtual['atualizado_em'] > $registroOriginal['atualizado_em'], 'Data de atualização não avançou.');
    conferirNome($lerRegistro($outroId) === $outroOriginal, 'Modificou outro usuário.');
    $novaConexao = new PDO("mysql:host={$db['host']};port={$db['port']};dbname=$banco;charset=utf8mb4", $db['usuario'], $db['senha']);
    $persistido = $novaConexao->prepare('SELECT nome FROM usuarios WHERE id=?');
    $persistido->execute([$id]);
    conferirNome($persistido->fetchColumn() === 'João D’Ávila', 'Nome não persistiu para outra conexão.');
    $novaConexao = null;
    [$status, $html] = requisicaoNome($base, $cookie);
    conferirNome($status === 200 && str_contains($html, 'Nome atualizado com sucesso.') && str_contains($html, 'João D’Ávila'), 'Nome/feedback não atualizados.');
    conferirNome(!str_contains(requisicaoNome($base, $cookie)[1], 'Nome atualizado com sucesso.'), 'Mensagem de sucesso repetida.');
    conferirNome(str_contains(requisicaoNome($base, $cookie, null, '/app/inicio/')[1], 'João'), 'Nome não refletido em outra página.');
    session_id($sessaoId); session_start();
    conferirNome($_SESSION['usuario']['nome'] === 'João D’Ávila', 'Nome da sessão desatualizado.');
    session_write_close();
    $relogin = (new AuthService())->autenticar('nome@example.test', 'SenhaTeste@123');
    conferirNome($relogin['sucesso'] && $relogin['usuario']['nome'] === 'João D’Ávila', 'Novo login não refletiu nome salvo.');
    conferirNome(requisicaoNome($base, $cookie, $post)[0] === 303 && $lerRegistro($id) === $registroAtual, 'Reenvio do mesmo nome alterou o registro.');
    $post['nome'] = str_repeat('Á', 150);
    conferirNome(requisicaoNome($base, $cookie, $post)[0] === 303, 'Limite de 150 caracteres rejeitado.');
    $post['nome'] = '<img src=x onerror=alert(1)> Silva';
    conferirNome(requisicaoNome($base, $cookie, $post)[0] === 303, 'Nome de teste não salvo.');
    $html = requisicaoNome($base, $cookie)[1];
    conferirNome(str_contains($html, '&lt;img src=x onerror=alert(1)&gt; Silva') && !str_contains($html, $post['nome']), 'Nome não escapado no HTML.');
    $post['acao'] = 'alterar_foto';
    [$status, $html] = requisicaoNome($base, $cookie, $post);
    conferirNome($status === 422 && str_contains($html, 'class="conta-foto-editor" open') && !str_contains($html, 'class="conta-nome-editor" open'), 'Fluxo da foto foi afetado.');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6nQAAAAASUVORK5CYII=');
    file_put_contents($fotoTeste, $png);
    $antesFoto = $lerRegistro($id);
    $upload = ['acao' => 'alterar_foto', 'csrf_token' => $csrf, 'foto' => new CURLFile($fotoTeste, 'image/png', 'avatar.png')];
    conferirNome(requisicaoNome($base, $cookie, $upload, '/app/conta/', true)[0] === 303, 'Upload válido não foi salvo.');
    $depoisFoto = $lerRegistro($id);
    conferirNome($depoisFoto['foto_personalizada'] === 'data:image/png;base64,' . base64_encode($png), 'Foto divergente no banco.');
    $semFoto = static fn(array $registro): array => array_diff_key($registro, ['foto_personalizada' => true, 'atualizado_em' => true]);
    conferirNome($semFoto($antesFoto) === $semFoto($depoisFoto), 'Upload alterou nome ou outros dados.');
    conferirNome(str_contains(requisicaoNome($base, $cookie)[1], $depoisFoto['foto_personalizada']), 'Foto salva não apareceu no perfil.');
    $post['acao'] = 'alterar_nome';
    $post['nome'] = 'Nome após a foto';
    conferirNome(requisicaoNome($base, $cookie, $post)[0] === 303 && $lerRegistro($id)['foto_personalizada'] === $depoisFoto['foto_personalizada'], 'Alterar nome apagou a foto.');
    $pdo->prepare("UPDATE usuarios SET status='inativo' WHERE id=?")->execute([$id]);
    $post['nome'] = 'Não deve salvar';
    conferirNome(requisicaoNome($base, $cookie, $post)[0] === 302 && $lerRegistro($id)['nome'] === 'Nome após a foto', 'Conta inativa conseguiu editar nome.');
} finally {
    if (is_resource($processo)) { proc_terminate($processo); proc_close($processo); }
    if (isset($sessaoId)) { if (session_status() !== PHP_SESSION_ACTIVE) { session_id($sessaoId); session_start(); } $_SESSION = []; session_destroy(); }
    (new ReflectionProperty(Database::class, 'conexao'))->setValue(null, null);
    $pdo = null;
    if ($criado && preg_match('/^ecodrop_test_[a-f0-9]{12}$/', $banco)) $servidor->exec("DROP DATABASE `$banco`");
    unlink($router); unlink($log); unlink($fotoTeste);
}
echo "$checagens verificações aprovadas: persistência do nome/foto, integridade dos demais campos, datas, novo login, CSRF, sessão, isolamento e escape HTML. Banco temporário removido.\n";
