<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';


final class RateLimiter
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::conexao();
    }

    /**
     * Verifica se já houve tentativas demais para o identificador na janela.
     * Aproveita para limpar registros antigos e manter a tabela pequena.
     */
    public function demasiadasTentativas(string $escopo, string $identificador, int $maximo, int $janelaSegundos): bool
    {
        $this->limparAntigos($janelaSegundos);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM tentativas_acesso
              WHERE escopo = :escopo AND identificador = :identificador AND criado_em >= :desde'
        );
        $stmt->execute([
            'escopo' => $escopo,
            'identificador' => $this->hash($identificador),
            'desde' => date('Y-m-d H:i:s', time() - $janelaSegundos),
        ]);

        return (int) $stmt->fetchColumn() >= $maximo;
    }

    /** Registra uma tentativa malsucedida. */
    public function registrar(string $escopo, string $identificador): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tentativas_acesso (escopo, identificador, ip) VALUES (:escopo, :identificador, :ip)'
        );
        $stmt->execute([
            'escopo' => $escopo,
            'identificador' => $this->hash($identificador),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    /**
     * Reserva uma tentativa sem ultrapassar o limite, em uma única transação.
     *
     * A checagem antiga seguida de INSERT permitia que requisições concorrentes
     * observassem o mesmo contador antes de gravar. O SELECT ... FOR UPDATE
     * bloqueia o intervalo do índice para este escopo e identificador até o
     * INSERT ser confirmado.
     */
    public function registrarSePermitido(
        string $escopo,
        string $identificador,
        int $maximo,
        int $janelaSegundos
    ): bool {
        return $this->registrarTodosSePermitido([[
            'escopo' => $escopo,
            'identificador' => $identificador,
            'maximo' => $maximo,
            'janela' => $janelaSegundos,
        ]]);
    }

    /**
     * Versão atômica para fluxos protegidos simultaneamente por IP e e-mail.
     * Retorna false sem consumir nenhum dos limites quando qualquer um deles
     * já estiver esgotado.
     *
     * @param list<array{escopo:string, identificador:string, maximo:int, janela:int}> $tentativas
     */
    public function registrarTodosSePermitido(array $tentativas): bool
    {
        if ($tentativas === []) {
            throw new InvalidArgumentException('Informe ao menos um limite de tentativa.');
        }

        $normalizadas = [];
        $maiorJanela = 0;
        foreach ($tentativas as $tentativa) {
            if ($tentativa['maximo'] < 1 || $tentativa['janela'] < 1) {
                throw new InvalidArgumentException('Os limites de tentativa devem ser positivos.');
            }
            $normalizadas[] = [
                'escopo' => $tentativa['escopo'],
                'identificador' => $this->hash($tentativa['identificador']),
                'maximo' => $tentativa['maximo'],
                'janela' => $tentativa['janela'],
            ];
            $maiorJanela = max($maiorJanela, $tentativa['janela']);
        }

        // Uma ordem estável evita deadlock quando duas requisições reservam
        // limites de IP e e-mail em paralelo.
        usort($normalizadas, static fn (array $a, array $b): int =>
            [$a['escopo'], $a['identificador']] <=> [$b['escopo'], $b['identificador']]
        );
        $this->limparAntigos($maiorJanela);

        return Database::transacao(function (PDO $pdo) use ($normalizadas): bool {
            $agora = time();
            foreach ($normalizadas as $tentativa) {
                // Buscar os IDs, em vez de COUNT(*), garante que InnoDB aplique
                // os locks de registro/intervalo necessários ao índice composto.
                $bloquear = $pdo->prepare(
                    'SELECT id FROM tentativas_acesso
                     WHERE escopo = :escopo
                       AND identificador = :identificador
                       AND criado_em >= :desde
                     FOR UPDATE'
                );
                $bloquear->execute([
                    'escopo' => $tentativa['escopo'],
                    'identificador' => $tentativa['identificador'],
                    'desde' => date('Y-m-d H:i:s', $agora - $tentativa['janela']),
                ]);

                if (count($bloquear->fetchAll()) >= $tentativa['maximo']) {
                    return false;
                }
            }

            $inserir = $pdo->prepare(
                'INSERT INTO tentativas_acesso (escopo, identificador, ip) VALUES (:escopo, :identificador, :ip)'
            );
            foreach ($normalizadas as $tentativa) {
                $inserir->execute([
                    'escopo' => $tentativa['escopo'],
                    'identificador' => $tentativa['identificador'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            }

            return true;
        });
    }

    /** Zera o contador (ex.: após um login bem-sucedido). */
    public function limpar(string $escopo, string $identificador): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM tentativas_acesso WHERE escopo = :escopo AND identificador = :identificador'
        );
        $stmt->execute(['escopo' => $escopo, 'identificador' => $this->hash($identificador)]);
    }

    private function limparAntigos(int $janelaSegundos): void
    {
        $limite = date('Y-m-d H:i:s', time() - max($janelaSegundos * 4, 3600));
        $stmt = $this->pdo->prepare('DELETE FROM tentativas_acesso WHERE criado_em < :limite');
        $stmt->execute(['limite' => $limite]);
    }

    private function hash(string $identificador): string
    {
        return hash('sha256', $identificador);
    }
}
