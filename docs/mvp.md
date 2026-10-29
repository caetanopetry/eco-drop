# MVP

## Objetivo

O MVP deverá demonstrar o ciclo principal do EcoDrop, cobrindo desde o cadastro do usuário até o resgate de recompensas com pontos.

## Ciclo principal

1. Pessoa cria sua conta.
2. Pessoa realiza login.
3. Usuário seleciona uma estação ativa em “Meus Descartes”.
4. Usuário informa material e quantidade da pesagem.
5. Sistema valida o descarte e calcula os pontos.
6. Pontos são creditados ao usuário.
7. Descarte aparece no histórico.
8. Movimentação aparece no extrato de pontos.
9. Pontos obtidos no mês contam para o ranking.
10. Usuário consulta recompensas.
11. Usuário resgata uma recompensa caso tenha saldo.
12. Sistema debita os pontos.
13. Sistema emite voucher quando aplicável.

## Registro de pesagem

O painel do usuário permite registrar uma pesagem manualmente, informando:

- estação;
- material;
- quantidade;
- unidade correspondente.

O sistema valida a estação, o material, a quantidade mínima e a pontuação antes de registrar o descarte.

## Restrições do MVP

- O usuário comum registra pesagens diretamente no aplicativo.
- Hardware real não faz parte do MVP.
- Parceiros não possuem painel próprio no MVP.

## Requisitos diretamente relacionados

- Cadastro, autenticação e perfil: [`RF001`](requisitos/requisitos-funcionais.md#rf001--cadastro-de-usuário), [`RF002`](requisitos/requisitos-funcionais.md#rf002--autenticação), [`RF003`](requisitos/requisitos-funcionais.md#rf003--logout), [`RF004`](requisitos/requisitos-funcionais.md#rf004--perfil), [`RF005`](requisitos/requisitos-funcionais.md#rf005--recuperação-de-acesso).
- Estações e descartes: [`RF006`](requisitos/requisitos-funcionais.md#rf006--consulta-de-estações), [`RF015`](requisitos/requisitos-funcionais.md#rf015--registro-de-descarte), [`RF018`](requisitos/requisitos-funcionais.md#rf018--validação-de-quantidade-mínima).
- Descartes e pontos: [`RF015`](requisitos/requisitos-funcionais.md#rf015--registro-de-descarte), [`RF018`](requisitos/requisitos-funcionais.md#rf018--validação-de-quantidade-mínima), [`RF019`](requisitos/requisitos-funcionais.md#rf019--registro-manual-de-pesagem), [`RF022`](requisitos/requisitos-funcionais.md#rf022--cálculo-de-pontos), [`RF025`](requisitos/requisitos-funcionais.md#rf025--extrato-de-pontos).
- Ranking e recompensas: [`RF037`](requisitos/requisitos-funcionais.md#rf037--ranking-mensal), [`RF029`](requisitos/requisitos-funcionais.md#rf029--catálogo-de-recompensas), [`RF031`](requisitos/requisitos-funcionais.md#rf031--resgate-de-recompensa), [`RF032`](requisitos/requisitos-funcionais.md#rf032--emissão-de-voucher).
