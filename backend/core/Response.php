<?php

declare(strict_types=1);

/**
 * Padroniza as respostas JSON dos endpoints da API.
 *
 * Formato de sucesso: { "sucesso": true, "dados": ... }
 * Formato de erro:    { "sucesso": false, "erro": { "mensagem": "...", "campos": {...} } }
 */
final class Response
{
    private function __construct()
    {
    }

    public static function sucesso(mixed $dados = null, int $codigoHttp = 200): never
    {
        self::enviar($codigoHttp, [
            'sucesso' => true,
            'dados' => $dados,
        ]);
    }

    public static function erro(string $mensagem, int $codigoHttp = 400, array $campos = []): never
    {
        $corpo = [
            'sucesso' => false,
            'erro' => [
                'mensagem' => $mensagem,
            ],
        ];

        if (!empty($campos)) {
            $corpo['erro']['campos'] = $campos;
        }

        self::enviar($codigoHttp, $corpo);
    }

    private static function enviar(int $codigoHttp, array $corpo): never
    {
        if (!headers_sent()) {
            http_response_code($codigoHttp);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
