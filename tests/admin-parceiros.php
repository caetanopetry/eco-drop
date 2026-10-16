<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../backend/repositories/PropostasParceriaRepository.php';
require_once __DIR__.'/../backend/core/Auth.php';
require_once __DIR__.'/../backend/core/Csrf.php';
function conferir(bool $condicao, string $mensagem): void {
    if (!$condicao) throw new RuntimeException($mensagem);
}
$pdo=Database::conexao();
$pdo->beginTransaction();
Auth::iniciarSessao();
try {
    $admin=$pdo->query("SELECT id,nome,email,papel FROM usuarios WHERE papel='admin' AND status='ativo' LIMIT 1")->fetch();
    conferir((bool)$admin,'É necessária uma conta administrativa ativa.');
    $repo=new PropostasParceriaRepository();
    $entrada=['organizacao'=>'Teste <script>alert(1)</script>', 'responsavel'=>'Responsável teste',
        'email'=>'teste@example.com','telefone'=>'11999999999','cidade'=>'São Paulo / SP',
        'segmento'=>'Comércio','tipo'=>'Recompensas','proposta'=>'Proposta de teste temporária'];
    foreach (['email'=>['incorreto'], 'proposta'=>[str_repeat('x',5001)], 'telefone'=>[['inválido']]] as $campo=>$valores) {
        foreach ($valores as $valor) {
            try { $repo->receber(array_replace($entrada,[$campo=>$valor])); throw new RuntimeException('Entrada inválida aceita.'); }
            catch (InvalidArgumentException $e) { /* esperado */ }
        }
    }
    $id=$repo->receber($entrada);
    $buscar=static function() use ($pdo,$id) { $s=$pdo->prepare('SELECT * FROM propostas_parceria WHERE id=?'); $s->execute([$id]); return $s->fetch(); };
    conferir($buscar()['status']==='nova','Nova proposta deve entrar como nova.');
    foreach (['em_analise','aprovada','recusada'] as $status) {
        $repo->avaliar($id,$status,'Observação interna',(int)$admin['id']); $p=$buscar();
        conferir($p['status']===$status && (int)$p['avaliado_por']===(int)$admin['id'] && $p['avaliado_em']!==null,'Avaliação não persistida.');
    }
    try { $repo->avaliar($id,'inexistente','',(int)$admin['id']); throw new RuntimeException('Status inválido aceito.'); }
    catch (InvalidArgumentException $e) { /* esperado */ }
    conferir(!Csrf::validar('inválido') && Csrf::validar(Csrf::token()),'Validação CSRF incorreta.');
    $_SESSION['usuario']=$admin; $_SERVER['REQUEST_METHOD']='GET';
    $_GET=['busca'=>'Teste <script>','status'=>'recusada'];
    ob_start(); require __DIR__.'/../frontend/pages/admin/parceiros.php'; $html=ob_get_clean();
    conferir(str_contains($html,'Teste &lt;script&gt;alert(1)&lt;/script&gt;'),'Dados devem ser escapados.');
    conferir(!str_contains($html,'Teste <script>alert(1)</script>'),'HTML injetado.');
    conferir(str_contains($html,'1 de ') && str_contains($html,'Avaliação interna'),'Filtro ou formulário ausente.');
    echo "Cadastro, validação, avaliação, filtros, renderização e CSRF aprovados. Dados de teste revertidos.\n";
} finally {
    $pdo->rollBack(); $_SESSION=[]; session_destroy();
}
