<?php
declare(strict_types=1);

// Somente CLI. Cria e remove um banco exclusivo; nunca usa as contas do banco real.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../backend/core/Config.php';
require_once __DIR__ . '/../backend/core/Auth.php';
require_once __DIR__ . '/../backend/core/Csrf.php';
require_once __DIR__ . '/../backend/core/Request.php';
require_once __DIR__ . '/../backend/services/AuthService.php';
require_once __DIR__ . '/../backend/services/PasswordRecoveryService.php';
require_once __DIR__ . '/../backend/repositories/EcoDropRepository.php';
require_once __DIR__ . '/../backend/repositories/AdminUsuariosRepository.php';
require_once __DIR__ . '/../backend/repositories/AdminDashboardRepository.php';

function conferir(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

function cpfTeste(string $base): string
{
    for ($posicao = 9; $posicao <= 10; $posicao++) {
        $soma = 0;
        for ($i = 0; $i < $posicao; $i++) $soma += (int) $base[$i] * ($posicao + 1 - $i);
        $digito = ($soma * 10) % 11;
        $base .= (string) ($digito === 10 ? 0 : $digito);
    }
    return $base;
}

$nomeBanco = 'ecodrop_test_' . bin2hex(random_bytes(6));
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', Config::get('database.host'), Config::get('database.port'));
$servidor = new PDO($dsn, (string) Config::get('database.usuario'), (string) Config::get('database.senha'), $options);
$pdo = null;
$conexao = new ReflectionProperty(Database::class, 'conexao');
$criado = false;
$falha = null;
$testes = [];
Auth::iniciarSessao();
try {
    $servidor->exec("CREATE DATABASE `$nomeBanco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $criado = true;
    $pdo = new PDO($dsn . ';dbname=' . $nomeBanco, (string) Config::get('database.usuario'), (string) Config::get('database.senha'), $options);
    $conexao->setValue(null, $pdo);
    $schema = file_get_contents(__DIR__ . '/../backend/database/schema.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS ecodrop\s+.*?;/s', '', $schema);
    $schema = str_replace('USE ecodrop;', '', $schema);
    $pdo->exec($schema);
    $seed = str_replace('USE ecodrop;', '', file_get_contents(__DIR__ . '/../backend/database/seed.sql'));
    $pdo->exec($seed);
    $pdo->exec(file_get_contents(__DIR__ . '/../backend/database/migrations/20261008_create_sessoes_acesso.sql'));
    $testes[] = 'schema novo, seed e migração de acessos idempotente';

    $auth = new AuthService();
    $cpf = cpfTeste('123456789');
    conferir(Validator::cpf($cpf), 'CPF de teste inválido');
    $registro = $auth->registrar('Conta de revisão', 'review@example.com', $cpf, 'TesteSeguro@123');
    conferir($registro['sucesso'], 'Cadastro válido falhou');
    $id = (int) $registro['usuario']['id'];
    conferir(!$auth->registrar('Duplicada', 'review@example.com', cpfTeste('987654321'), 'TesteSeguro@123')['sucesso'], 'E-mail duplicado aceito');
    conferir(!$auth->registrar('Duplicada', 'outra@example.com', $cpf, 'TesteSeguro@123')['sucesso'], 'CPF duplicado aceito');
    conferir(!$auth->registrar('', 'invalido', '00000000000', '123')['sucesso'], 'Cadastro inválido aceito');
    conferir(!$auth->registrar(str_repeat('Á', 151), 'nome@example.com', cpfTeste('987654321'), 'TesteSeguro@123')['sucesso'], 'Nome maior que a coluna aceito');
    conferir(!$auth->autenticar('review@example.com', 'errada')['sucesso'], 'Senha inválida aceita');
    $login = $auth->autenticar('REVIEW@example.com', 'TesteSeguro@123');
    conferir($login['sucesso'], 'Login por e-mail normalizado falhou');
    $testes[] = 'cadastro, duplicidades, validação e senha';

    Auth::autenticar($login['usuario']);
    conferir(Auth::usuarioLogado()['id'] === $id, 'Sessão não autenticada');
    conferir(Csrf::validar(Csrf::token()) && !Csrf::validar('invalido'), 'CSRF incorreto');
    $sessao = session_id();
    $usuarios = new AdminUsuariosRepository();
    $linha = static fn (array $dados): array => array_values(array_filter($dados['contas'], static fn ($c) => $c[0] === $id))[0];
    conferir($linha($usuarios->visaoGeral())[5] === 'Online', 'Login não apareceu online');
    (new SessaoAcessoRepository())->registrar($id, 'sessao-adicional-de-teste');
    Auth::encerrarSessao();
    conferir($linha($usuarios->visaoGeral())[5] === 'Online', 'Logout encerrou outra sessão');
    (new SessaoAcessoRepository())->encerrar('sessao-adicional-de-teste');
    conferir($linha($usuarios->visaoGeral())[5] === 'Offline', 'Logout não apareceu offline');
    $testes[] = 'sessões, CSRF, múltiplos acessos e logout';

    $stmt = $pdo->prepare("UPDATE usuarios SET status='bloqueado' WHERE id=?"); $stmt->execute([$id]);
    conferir(!$auth->autenticar('review@example.com', 'TesteSeguro@123')['sucesso'], 'Conta bloqueada fez login');
    $stmt = $pdo->prepare("UPDATE usuarios SET status='ativo' WHERE id=?"); $stmt->execute([$id]);
    conferir($auth->vincularGoogle($id, 'identidade-google-de-teste'), 'Vinculação falhou');
    conferir($auth->autenticarGoogle('identidade-google-de-teste')['sucesso'], 'Login Google por identidade vinculada falhou');
    $testes[] = 'bloqueio no login e identidade Google vinculada (sem chamada externa)';

    $eco = new EcoDropRepository();
    $eco->prepararDemo();
    $estacao = (int) $eco->estacoesAtivas()[0]['id'];
    $material = $pdo->query("SELECT * FROM materiais WHERE slug='plastico'")->fetch();
    $sessaoEstacao = $eco->criarSessaoEstacao($estacao);
    conferir($eco->associarSessao($sessaoEstacao['token'], $id)['sucesso'], 'Associação de QR válido falhou');
    conferir(!$eco->associarSessao($sessaoEstacao['token'], $id)['sucesso'], 'QR associado reutilizado');
    foreach ([INF, NAN, -1, 10000000] as $quantidadeInvalida) {
        conferir(!$eco->registrarDescarte($sessaoEstacao['token'], (int) $material['id'], $quantidadeInvalida)['sucesso'], 'Quantidade fora do limite aceita');
    }
    $descarte = $eco->registrarDescarte($sessaoEstacao['token'], (int) $material['id'], 2);
    conferir($descarte['sucesso'] && $descarte['pontos'] === 300, 'Pontuação por descarte incorreta');
    conferir($eco->saldoDoUsuario($id) === 400, 'Saldo/bonus do primeiro descarte incorreto');
    conferir(!$eco->registrarDescarte($sessaoEstacao['token'], (int) $material['id'], 2)['sucesso'], 'Descarte duplicado aceito');
    $testes[] = 'QR, descarte, crédito, bônus único e bloqueio de repetição';

    $sessaoEstacao = $eco->criarSessaoEstacao($estacao);
    conferir($eco->associarSessao($sessaoEstacao['token'], $id)['sucesso'], 'Segundo QR falhou');
    $descarte = $eco->registrarDescarte($sessaoEstacao['token'], (int) $material['id'], .01);
    conferir($descarte['sucesso'] && $descarte['pontos'] === 0 && $eco->saldoDoUsuario($id) === 400, 'Descarte abaixo do mínimo pontuou');
    $sessaoEstacao = $eco->criarSessaoEstacao($estacao);
    $stmt = $pdo->prepare('UPDATE sessoes_estacao SET expira_em = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id=?'); $stmt->execute([$sessaoEstacao['id']]);
    conferir(!$eco->associarSessao($sessaoEstacao['token'], $id)['sucesso'], 'QR expirado aceito');
    $testes[] = 'quantidade mínima e expiração do QR';

    $recompensa = $pdo->query("SELECT * FROM recompensas WHERE custo_pontos=300 LIMIT 1")->fetch();
    conferir($eco->resgatarRecompensa($id, (int) $recompensa['id'])['sucesso'], 'Resgate válido falhou');
    conferir($eco->saldoDoUsuario($id) === 100, 'Saldo após débito incorreto');
    conferir(count($eco->vouchersDoUsuario($id)) === 1, 'Voucher não emitido');
    conferir(!$eco->resgatarRecompensa($id, (int) $recompensa['id'])['sucesso'], 'Resgate com saldo insuficiente aceito');
    conferir((int) $pdo->query('SELECT estoque FROM recompensas WHERE id=' . (int) $recompensa['id'])->fetchColumn() === (int) $recompensa['estoque'] - 1, 'Estoque incorreto');
    $testes[] = 'resgate, débito, voucher, saldo insuficiente e estoque';

    $dados = $usuarios->visaoGeral();
    conferir($linha($dados)[9] === 100 && $linha($dados)[10] === 2, 'Admin diverge do saldo/histórico');
    conferir($dados['indicadores']['total'] === (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(), 'Total do admin divergente');
    conferir(array_sum(array_column($dados['grafico'], 'total')) === $dados['indicadores']['novas'], 'Gráfico divergente');
    conferir($eco->posicaoUsuarioNoMes($id) !== null, 'Ranking não incluiu o usuário');
    conferir(count($eco->extratoDoUsuario($id)) === 3 && count($eco->descartesDoUsuario($id)) === 2, 'Extrato/histórico incorretos');
    conferir((new AdminDashboardRepository())->indicadores()['descartes_registrados'] === 2, 'Dashboard divergente');
    $testes[] = 'admin real, gráfico, saldo, descartes, extrato e ranking';

    Auth::iniciarSessao();
    Auth::autenticar($login['usuario']);
    $usuarioRepo = new UsuarioRepository();
    $recuperacao = new PasswordRecoveryService();
    $token = bin2hex(random_bytes(32));
    $usuarioRepo->criarRecuperacao($id, hash('sha256', $token), gmdate('Y-m-d H:i:s', time() + 3600));
    conferir($recuperacao->tokenValido($token) && !$recuperacao->tokenValido('invalido'), 'Validação do token incorreta');
    $novaSenha = ' NovaSenha@123 ';
    conferir($recuperacao->redefinirSenha($token, $novaSenha), 'Redefinição falhou');
    conferir(!$recuperacao->redefinirSenha($token, 'OutraSenha@123'), 'Token reutilizado');
    conferir(Auth::usuarioLogado() === null, 'Senha alterada não revogou a sessão');
    conferir(!$auth->autenticar('review@example.com', 'TesteSeguro@123')['sucesso'], 'Senha anterior continua válida');
    conferir($auth->autenticar('review@example.com', $novaSenha)['sucesso'], 'Senha com espaços não foi preservada');
    $_POST = ['senha' => $novaSenha, 'nome' => ' Nome '];
    $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    conferir(Request::texto('senha', '', false) === $novaSenha && Request::texto('nome') === 'Nome', 'Leitura de senha alterou caracteres');
    $testes[] = 'redefinição, uso único, senha com espaços e revogação de sessão';

    $expirado = bin2hex(random_bytes(32));
    $usuarioRepo->criarRecuperacao($id, hash('sha256', $expirado), gmdate('Y-m-d H:i:s', time() - 60));
    conferir(!$recuperacao->tokenValido($expirado) && !$recuperacao->redefinirSenha($expirado, 'Teste@123'), 'Token expirado aceito');
    $anterior = bin2hex(random_bytes(32));
    $atual = bin2hex(random_bytes(32));
    $usuarioRepo->criarRecuperacao($id, hash('sha256', $anterior), gmdate('Y-m-d H:i:s', time() + 3600));
    $usuarioRepo->criarRecuperacao($id, hash('sha256', $atual), gmdate('Y-m-d H:i:s', time() + 3600));
    conferir(!$recuperacao->tokenValido($anterior) && $recuperacao->tokenValido($atual), 'Solicitação nova não invalidou token anterior');
    $usuarioRepo->invalidarRecuperacao(hash('sha256', $anterior));
    conferir($recuperacao->tokenValido($atual), 'Falha de envio antiga invalidou um token mais recente');
    $testes[] = 'expiração e invalidação de tokens anteriores, sem envio de e-mail';

    Auth::iniciarSessao();
    Auth::autenticar($auth->autenticar('review@example.com', $novaSenha)['usuario']);
    $stmt = $pdo->prepare("UPDATE usuarios SET status='bloqueado' WHERE id=?"); $stmt->execute([$id]);
    conferir(Auth::usuarioLogado() === null, 'Conta bloqueada manteve sessão');
    $stmt = $pdo->prepare("UPDATE usuarios SET status='ativo' WHERE id=?"); $stmt->execute([$id]);
    $testes[] = 'bloqueio no banco revoga sessão já aberta';

    // Gera mais de dez posições para verificar que a posição pessoal não é truncada.
    for ($i = 0; $i < 11; $i++) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome,email,cpf,senha_hash) VALUES (?, ?, ?, 'fixture')");
        $stmt->execute(['Ranking ' . $i, "ranking$i@example.com", str_pad((string) $i, 11, '8', STR_PAD_LEFT)]);
        $rankingId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO movimentacoes_pontos (usuario_id,tipo,origem,quantidade,descricao) VALUES (?, 'credito', 'descarte', 500, 'Fixture ranking')");
        $stmt->execute([$rankingId]);
    }
    conferir(count($eco->rankingMensal()) === 10 && $eco->posicaoUsuarioNoMes($id) === 12, 'Posição fora do top 10 incorreta');
    $testes[] = 'ranking pessoal além das dez primeiras posições';

    $stmt = $pdo->prepare("INSERT INTO movimentacoes_pontos (usuario_id,tipo,origem,quantidade,descricao) VALUES (?, 'credito', 'estorno', 300, 'Fixture concorrência')"); $stmt->execute([$id]);
    $pdo->exec("INSERT INTO recompensas (nome,custo_pontos,estoque,status) VALUES ('Recompensa concorrente',300,5,'ativo')");
    $segundaRecompensa = (int) $pdo->lastInsertId();
    // Dá tempo para dois processos chegarem ao débito; só um pode usar o saldo de 400.
    $pdo->exec('CREATE TRIGGER teste_resgate BEFORE INSERT ON resgates FOR EACH ROW DO SLEEP(0.5)');
    $workers = [];
    foreach ([(int) $recompensa['id'], $segundaRecompensa] as $recompensaId) {
        $pipes = [];
        $processo = proc_open([PHP_BINARY, __DIR__ . '/resgate-worker.php', $nomeBanco, (string) $id, (string) $recompensaId], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__));
        conferir(is_resource($processo), 'Não foi possível iniciar o teste de concorrência');
        fclose($pipes[0]);
        $workers[] = [$processo,$pipes];
    }
    $sucessos = 0;
    foreach ($workers as [$processo,$pipes]) {
        $saida = stream_get_contents($pipes[1]); $erro = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        conferir(proc_close($processo) === 0, 'Processo concorrente falhou: ' . $erro);
        $sucessos += (int) (json_decode($saida, true, 512, JSON_THROW_ON_ERROR)['sucesso']);
    }
    $pdo->exec('DROP TRIGGER teste_resgate');
    conferir($sucessos === 1 && $eco->saldoDoUsuario($id) === 100, 'Resgates concorrentes gastaram saldo em duplicidade');
    $testes[] = 'dois processos resgatando recompensas diferentes sem saldo duplicado';

    // Simula banco ainda sem a migração para testar a tolerância do histórico.
    Auth::iniciarSessao();
    $pdo->exec('RENAME TABLE sessoes_acesso TO sessoes_acesso_pendente');
    try {
        Auth::autenticar($login['usuario']);
        conferir(Auth::usuarioLogado()['id'] === $id, 'Histórico indisponível bloqueou sessão');
        Auth::encerrarSessao();
    } finally {
        $pdo->exec('RENAME TABLE sessoes_acesso_pendente TO sessoes_acesso');
    }
    $testes[] = 'login e logout preservados com migração de histórico pendente';
} catch (Throwable $e) {
    $falha = $e;
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION = []; session_destroy(); }
    $conexao->setValue(null, null);
    $pdo = null;
    if ($criado && preg_match('/^ecodrop_test_[a-f0-9]{12}$/', $nomeBanco)) $servidor->exec("DROP DATABASE `$nomeBanco`");
}
foreach ($testes as $teste) echo "OK: $teste\n";
if ($falha !== null) {
    fwrite(STDERR, 'FALHA: ' . $falha->getMessage() . "\n");
    exit(1);
}
echo count($testes) . " grupos de testes aprovados; banco temporário removido.\n";
