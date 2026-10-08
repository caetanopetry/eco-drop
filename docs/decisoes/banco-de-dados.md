# Decisão — Banco de dados

> Este documento supre a ausência de `/docs/banco-de-dados/README.md` no repositório
> no momento da implementação. Deve ser tratado como a documentação de referência do
> schema até que a equipe do projeto formalize (ou substitua) este conteúdo no local
> oficial.

## Motor e convenções

- MySQL/MariaDB, engine InnoDB (suporte a transações e chaves estrangeiras — RNF006).
- Charset `utf8mb4` / collation `utf8mb4_unicode_ci`.
- Chaves estrangeiras com `ON DELETE RESTRICT` para tabelas históricas/financeiras
  (descartes, movimentações, resgates, vouchers, auditoria), para nunca perder
  rastreabilidade (RNF007, RNF017) apagando um usuário ou estação.

## Decisões de modelagem e por quê

### Sem tabela separada de "administradores"

`usuarios.papel` (`usuario` | `admin`) diferencia AT01 de AT02. Um administrador é um
usuário com papel elevado, não uma entidade de negócio diferente — evita duplicar
campos (nome, e-mail, senha) e simplifica a autenticação (`AuthService` único).

### Saldo de pontos é calculado, não armazenado

Não existe coluna `usuarios.saldo`. O saldo é sempre `SUM(créditos) - SUM(débitos)`
em `movimentacoes_pontos` (ver `EcoDropRepository::saldoDoUsuario`). Isso segue RNF015
("crédito/débito de pontos não deve depender de valor enviado livremente pelo
cliente") e RNF006 (integridade): um saldo em cache poderia divergir do extrato em
caso de falha parcial. Se o desempenho exigir, um cache pode ser adicionado depois
como coluna derivada, recalculada a partir do extrato — nunca como fonte de verdade.

### Validade das sessões da estação

A validade das sessões da estação é lida de `configuracoes.chave =
'qr_code_validade_segundos'`, com padrão de 120 segundos quando não há valor
numérico. Não existe uma configuração paralela desse prazo no arquivo PHP.

### `movimentacoes_pontos` como extrato único

Toda alteração de pontos (descarte, bônus, resgate, estorno) gera uma linha aqui,
com `origem` e `origem_id` apontando para o registro que a gerou (RN013, RNF007).
O ranking mensal (RN029-RN032) é calculado filtrando `tipo = 'credito'` e
`criado_em` dentro do mês — não existe tabela de "ranking" persistida, pois o
ranking é sempre derivado, nunca fixado.

### `configuracoes` (chave/valor) para parâmetros pendentes

RN008, RN017 e RN039 exigem que valores comerciais (pontos por material, bônus de
primeiro descarte, custo de recompensas, premiações) sejam configuráveis e não
fiquem fixos no código. Os valores que a documentação marca como **PENDENTE**
(`docs/escopo.md`) foram inseridos como `NULL` em `materiais.pontos_por_unidade` e
em `configuracoes.valor` — nunca com um número inventado. A UI do Admin (Fase 5)
deve deixar claro quando um valor ainda não foi definido.

### QR Code / sessão de estação como token opaco

`sessoes_estacao.token` é uma string aleatória sem significado — nunca contém CPF,
e-mail ou qualquer dado sensível (RN019). O QR Code apresentado pela estação
codifica apenas esse token; a validação de validade (RN018, 2 minutos) é feita no
servidor via `expira_em`, nunca confiando em um timestamp vindo do cliente.

## Tabelas e sua origem na documentação

| Tabela | RFs/RNs principais |
| --- | --- |
| `usuarios` | RF001-RF005, RN014, RN015, RNF002 |
| `recuperacoes_acesso` | RF005 (hash do token, validade e utilização única) |
| `estacoes` | RF006-RF009, RF045 |
| `sessoes_estacao` | RF010-RF014, RN018-RN020, RNF008 |
| `materiais` | RN001-RN008 |
| `descartes` | RF015-RF023, RN001-RN013, RN021 |
| `movimentacoes_pontos` | RF024, RF025, RN009, RN011-RN013 |
| `configuracoes` | RN008, RN017, RN039 |
| `parceiros` | RF043, RN037, RN038 |
| `recompensas` | RF029, RF030, RN036, RN039 |
| `resgates` | RF031, RN022-RN024 |
| `vouchers` | RF032-RF035, RN025-RN027 |
| `premiacoes_ranking` | RF040, RF044, RN034, RN035 |
| `auditoria_administrativa` | RNF017, RN028 |

## Em aberto

- Índices adicionais e particionamento (se necessário) serão avaliados conforme
  volume real de dados — prematuro definir agora.
- Campos específicos de cadastro de estação (UC022 cita "campos PENDENTES") podem
  exigir colunas adicionais quando a documentação for atualizada.
