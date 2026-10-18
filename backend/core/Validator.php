<?php

declare(strict_types=1);

/**
 * Validações server-side reutilizáveis.
 *
 * Observação (docs/casos-de-uso/README.md, UC001): a forma de validação dos campos
 * de cadastro, além da unicidade de e-mail/CPF, é tratada como PENDENTE na documentação.
 * Aqui aplicamos apenas validações técnicas básicas e amplamente aceitas (formato de
 * e-mail, dígito verificador de CPF, tamanho mínimo de senha) — não regras de negócio
 * adicionais que a documentação ainda não aprovou.
 */
final class Validator
{
    private function __construct()
    {
    }

    public static function obrigatorio(string $valor): bool
    {
        return trim($valor) !== '';
    }

    public static function email(string $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function emailNormalizado(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }

    public static function normalizarEspacos(string $valor): string
    {
        return preg_replace('/\s+/', ' ', trim($valor)) ?? '';
    }

    public static function senhaForteMinima(string $valor): bool
    {
        // Mínimo técnico razoável. Política de senha mais rígida não está definida na documentação.
        // Exige 8 caracteres e ao menos um caractere que não seja espaço em branco.
        return mb_strlen($valor) >= 8 && trim($valor) !== '';
    }

    /**
     * Remove qualquer caractere que não seja dígito.
     */
    public static function somenteDigitos(string $valor): string
    {
        return preg_replace('/\D/', '', $valor) ?? '';
    }

    /**
     * Valida CPF pelo algoritmo padrão de dígitos verificadores.
     * Validação puramente técnica/matemática, não uma regra de negócio do EcoDrop.
     */
    public static function cpf(string $cpf): bool
    {
        $cpf = self::somenteDigitos($cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // Rejeita sequências repetidas (00000000000, 11111111111, ...), que passam
        // matematicamente no cálculo do dígito verificador mas nunca são CPFs válidos.
        if (preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($posicaoDigito = 9; $posicaoDigito <= 10; $posicaoDigito++) {
            $soma = 0;
            for ($i = 0; $i < $posicaoDigito; $i++) {
                $soma += (int) $cpf[$i] * (($posicaoDigito + 1) - $i);
            }
            $resto = ($soma * 10) % 11;
            $digitoCalculado = ($resto === 10) ? 0 : $resto;

            if ($digitoCalculado !== (int) $cpf[$posicaoDigito]) {
                return false;
            }
        }

        return true;
    }
}
