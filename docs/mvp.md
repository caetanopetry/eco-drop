# MVP

## Objetivo

O MVP deverá demonstrar o ciclo principal do EcoDrop, cobrindo desde o cadastro do usuário até o resgate de recompensas com pontos.

## Ciclo principal

1. Pessoa cria sua conta.
2. Pessoa realiza login.
3. Usuário localiza ou seleciona uma estação EcoDrop.
4. Estação cria uma sessão temporária.
5. Estação apresenta QR Code temporário.
6. Usuário autenticado escaneia o QR Code.
7. Sistema associa usuário e estação.
8. É realizado um descarte simulado.
9. São informados material e quantidade.
10. Sistema valida o descarte.
11. Sistema calcula os pontos.
12. Pontos são creditados ao usuário.
13. Descarte aparece no histórico.
14. Movimentação aparece no extrato de pontos.
15. Pontos obtidos no mês contam para o ranking.
16. Usuário consulta recompensas.
17. Usuário resgata uma recompensa caso tenha saldo.
18. Sistema debita os pontos.
19. Sistema emite voucher quando aplicável.

## Simulação da estação

Enquanto não houver hardware real, a interface da estação deverá possuir um modo de demonstração/simulação controlado.

Nesse modo, será possível informar manualmente:

- material;
- quantidade;
- unidade correspondente.

Essa funcionalidade representa os dados que futuramente poderiam ser fornecidos por balança, sensores ou outros componentes da estação.

## Restrições do MVP

- O usuário comum não deverá informar descartes diretamente pelo aplicativo para gerar pontos.
- A simulação de descarte pertence à interface controlada da estação.
- Hardware real não faz parte do MVP.
- Parceiros não possuem painel próprio no MVP.

## Requisitos diretamente relacionados

- Cadastro, autenticação e perfil: [`RF001`](requisitos/requisitos-funcionais.md#rf001--cadastro-de-usuário), [`RF002`](requisitos/requisitos-funcionais.md#rf002--autenticação), [`RF003`](requisitos/requisitos-funcionais.md#rf003--logout), [`RF004`](requisitos/requisitos-funcionais.md#rf004--perfil), [`RF005`](requisitos/requisitos-funcionais.md#rf005--recuperação-de-acesso).
- Estações e QR Code: [`RF006`](requisitos/requisitos-funcionais.md#rf006--consulta-de-estações), [`RF010`](requisitos/requisitos-funcionais.md#rf010--criação-de-sessão-da-estação), [`RF011`](requisitos/requisitos-funcionais.md#rf011--geração-de-qr-code), [`RF012`](requisitos/requisitos-funcionais.md#rf012--identificação-por-qr-code), [`RF014`](requisitos/requisitos-funcionais.md#rf014--expiração-de-qr-code).
- Descartes e pontos: [`RF015`](requisitos/requisitos-funcionais.md#rf015--registro-de-descarte), [`RF018`](requisitos/requisitos-funcionais.md#rf018--validação-de-quantidade-mínima), [`RF019`](requisitos/requisitos-funcionais.md#rf019--simulação-de-descarte), [`RF022`](requisitos/requisitos-funcionais.md#rf022--cálculo-de-pontos), [`RF025`](requisitos/requisitos-funcionais.md#rf025--extrato-de-pontos).
- Ranking e recompensas: [`RF037`](requisitos/requisitos-funcionais.md#rf037--ranking-mensal), [`RF029`](requisitos/requisitos-funcionais.md#rf029--catálogo-de-recompensas), [`RF031`](requisitos/requisitos-funcionais.md#rf031--resgate-de-recompensa), [`RF032`](requisitos/requisitos-funcionais.md#rf032--emissão-de-voucher).
