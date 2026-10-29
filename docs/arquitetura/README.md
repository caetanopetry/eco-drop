# Arquitetura

Este documento registra diretrizes iniciais de arquitetura sem escolher linguagem, framework ou stack de implementação.

## Diretrizes iniciais

- O MVP deve ser focado em software.
- A aplicação deve separar claramente as responsabilidades de usuário e administração.
- O cálculo de pontos, validações de descarte, resgates, estornos e regras de ranking não devem depender de valores enviados livremente pelo cliente.
- O extrato de pontos deve ser tratado como histórico de movimentações, não apenas como um campo numérico de saldo.
- Tokens temporários de recuperação de acesso devem possuir expiração.
- Dados pessoais, especialmente CPF e e-mail, não devem ser expostos publicamente.
- A arquitetura deve permitir futura integração com hardware sem tornar hardware obrigatório no MVP.

## Áreas lógicas do sistema

| Área | Responsabilidade |
| --- | --- |
| Conta e acesso | Cadastro, autenticação, logout, perfil, recuperação de acesso e status da conta. |
| Estações | Consulta, detalhes, mapa e status. |
| Descartes | Registro de pesagem pelo usuário, sessão técnica interna, validação e status. |
| Pontos | Cálculo, crédito, débito, saldo, extrato, bônus e estornos. |
| Ranking | Classificação mensal por pontos conquistados no período. |
| Recompensas | Catálogo, disponibilidade, custo em pontos e administração. |
| Resgates e vouchers | Registro de resgate, débito de pontos, emissão, validade e status de vouchers. |
| Administração | Gestão de usuários, estações, materiais, pontuação, recompensas, parceiros e premiações mensais. |

## Decisões registradas

- Backend implementado em PHP puro, sem framework nesta etapa.
- Frontend implementado em HTML e CSS, sem framework nesta etapa.

## Decisões ainda não tomadas

- Estratégia técnica de autenticação.
- Banco de dados específico.
- Integração definitiva com Google Maps.
- Forma futura de integração com balança, sensores, câmera, IA ou telemetria.

## Restrições desta etapa

Nenhuma arquitetura PHP, API, estrutura de aplicação, Docker, CI/CD, migration ou framework foi definido nesta etapa.
