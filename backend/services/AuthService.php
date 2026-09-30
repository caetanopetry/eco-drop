<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/UsuarioRepository.php';
require_once __DIR__ . '/../core/Validator.php';

/**
 * Regras de negócio de cadastro e autenticação.
 *
 * RF001 - Cadastro de usuário (nome, e-mail, CPF, senha)
 * RN014 - Unicidade de e-mail
 * RN015 - Unicidade de CPF
 * RNF002 - Senhas nunca em texto puro (password_hash / password_verify)
 */
final class AuthService
{
    public function __construct(private readonly UsuarioRepository $usuarios = new UsuarioRepository())
    {
    }

    /**
     * @return array{sucesso: bool, mensagem?: string, campos?: array<string,string>, usuario?: array}
     */
    public function registrar(string $nome, string $email, string $cpf, string $senha): array
    {
        $campos = [];
        $nome = Validator::normalizarEspacos($nome);
        $email = Validator::emailNormalizado($email);
        $cpfLimpo = Validator::somenteDigitos($cpf);

        if (!Validator::obrigatorio($nome)) {
            $campos['nome'] = 'Informe o nome completo.';
        }

        if (!Validator::obrigatorio($email) || !Validator::email($email) || mb_strlen($email) > 190) {
            $campos['email'] = 'Informe um e-mail válido.';
        }

        if (!Validator::cpf($cpfLimpo)) {
            $campos['cpf'] = 'Informe um CPF válido.';
        }

        if (!Validator::senhaForteMinima($senha)) {
            $campos['senha'] = 'A senha deve ter no mínimo 8 caracteres.';
        }

        if (!empty($campos)) {
            return ['sucesso' => false, 'mensagem' => 'Verifique os dados informados.', 'campos' => $campos];
        }

        // RN014 - Unicidade de e-mail
        if ($this->usuarios->buscarPorEmail($email) !== null) {
            return [
                'sucesso' => false,
                'mensagem' => 'Este e-mail já está associado a uma conta.',
                'campos' => ['email' => 'E-mail já cadastrado.'],
            ];
        }

        // RN015 - Unicidade de CPF
        if ($this->usuarios->buscarPorCpf($cpfLimpo) !== null) {
            return [
                'sucesso' => false,
                'mensagem' => 'Este CPF já está associado a uma conta.',
                'campos' => ['cpf' => 'CPF já cadastrado.'],
            ];
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $id = $this->usuarios->criar($nome, $email, $cpfLimpo, $senhaHash, 'usuario');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['sucesso' => false, 'mensagem' => 'E-mail ou CPF ja cadastrado.'];
            }

            throw $e;
        }

        return [
            'sucesso' => true,
            'usuario' => [
                'id' => $id,
                'nome' => $nome,
                'email' => $email,
                'papel' => 'usuario',
            ],
        ];
    }

    /**
     * @return array{sucesso: bool, mensagem?: string, usuario?: array}
     */
    public function autenticar(string $email, string $senha): array
    {
        $email = Validator::emailNormalizado($email);

        if (!Validator::obrigatorio($email) || !Validator::obrigatorio($senha)) {
            return ['sucesso' => false, 'mensagem' => 'Informe e-mail e senha.'];
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null || !password_verify($senha, $usuario['senha_hash'])) {
            return ['sucesso' => false, 'mensagem' => 'E-mail ou senha inválidos.'];
        }

        if ($usuario['status'] !== 'ativo') {
            return ['sucesso' => false, 'mensagem' => 'Esta conta está inativa. Procure o suporte.'];
        }

        return [
            'sucesso' => true,
            'usuario' => [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'papel' => $usuario['papel'],
            ],
        ];
    }
}
