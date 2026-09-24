# Visão geral

## Nome do projeto

EcoDrop

## Descrição

O EcoDrop é uma plataforma de software destinada a incentivar e gerenciar a coleta seletiva. A proposta é permitir que usuários utilizem estações EcoDrop, realizem descartes de materiais recicláveis, recebam pontos conforme o tipo e a quantidade de material descartado e utilizem esses pontos para obter recompensas.

O projeto está sendo desenvolvido no contexto do programa JEDI da Join.Valle.

## Conceito

O conceito original prevê uma estação física inteligente, potencialmente equipada no futuro com balança, câmera, sensores e inteligência artificial.

Nesta fase, o foco será exclusivamente o desenvolvimento dos sistemas de software:

- aplicação web responsiva para usuários;
- interface web da estação EcoDrop;
- painel administrativo;
- regras de pontuação, ranking, recompensas, resgates e vouchers.

## Limite importante

Hardware, balança, câmera, sensores, IA e automação física da estação não fazem parte do escopo inicial. O software deve ser projetado de forma que essas integrações possam ser estudadas futuramente, sem torná-las obrigatórias para o MVP.

## Materiais iniciais

| Material | Unidade | Quantidade mínima para descarte válido |
| --- | --- | --- |
| Plástico | kg | 0,100 kg |
| Papel | kg | 0,100 kg |
| Vidro | kg | 0,100 kg |
| Óleo de cozinha usado | L | 0,500 L |

Regras relacionadas: [`RN001`](requisitos/regras-de-negocio.md#rn001--materiais-aceitos), [`RN002`](requisitos/regras-de-negocio.md#rn002--unidade-dos-sólidos), [`RN003`](requisitos/regras-de-negocio.md#rn003--unidade-do-óleo), [`RN004`](requisitos/regras-de-negocio.md#rn004--quantidade-mínima-dos-sólidos), [`RN005`](requisitos/regras-de-negocio.md#rn005--quantidade-mínima-do-óleo).

## Pontuação

Os pontos serão calculados pela fórmula:

```text
quantidade descartada x pontos por unidade do material
```

Cada material possui valor próprio, configurável pelo administrador. Os valores numéricos ainda não foram definidos.

Os pontos devem ser sempre números inteiros. Caso o cálculo gere valor decimal, deverá ser aplicado arredondamento matemático para o inteiro mais próximo.

Regras relacionadas: [`RN006`](requisitos/regras-de-negocio.md#rn006--pontuação-por-quantidade), [`RN007`](requisitos/regras-de-negocio.md#rn007--pontuação-específica-por-material), [`RN008`](requisitos/regras-de-negocio.md#rn008--configuração-administrativa-da-pontuação), [`RN009`](requisitos/regras-de-negocio.md#rn009--pontos-inteiros), [`RN010`](requisitos/regras-de-negocio.md#rn010--arredondamento).
