# Regras de negócio

## RN001 - Materiais aceitos

Materiais iniciais: plástico, papel, vidro, metal, orgânico e óleo de cozinha usado.

## RN002 - Unidade dos sólidos

Plástico, papel, vidro, metal e orgânico são medidos em kg.

## RN003 - Unidade do óleo

Óleo é medido em litros.

## RN004 - Quantidade mínima dos sólidos

Plástico, papel, vidro, metal e orgânico exigem no mínimo 0,100 kg para descarte válido para pontuação.

## RN005 - Quantidade mínima do óleo

Óleo exige no mínimo 0,500 L para descarte válido para pontuação.

## RN006 - Pontuação por quantidade

Pontuação depende da quantidade válida descartada.

## RN007 - Pontuação específica por material

Cada material possui valor próprio de pontos por unidade.

## RN008 - Configuração administrativa da pontuação

Valores não deverão ficar fixos no código e deverão ser configuráveis.

## RN009 - Pontos inteiros

Saldo e movimentações utilizarão pontos inteiros.

## RN010 - Arredondamento

Resultados decimais serão arredondados matematicamente para inteiro mais próximo.

Exemplo de referência:

```text
0,750 kg x 150 = 112,5
Resultado: 113 pontos
```

## RN011 - Crédito após validação

Pontos somente serão creditados após descarte válido.

## RN012 - Pontos não expiram

Pontos normais da conta não possuem expiração.

## RN013 - Extrato

Toda movimentação relevante deverá ser registrada.

Exemplos de movimentação:

| Movimento | Origem |
| --- | --- |
| +150 | Descarte de plástico |
| +500 | Bônus de primeiro descarte |
| -600 | Resgate de recompensa |
| +600 | Estorno administrativo |

## RN014 - Unicidade de e-mail

Cada e-mail poderá estar associado a apenas uma conta.

## RN015 - Unicidade de CPF

Cada CPF poderá estar associado a apenas uma conta.

## RN016 - Bônus de primeiro descarte

Usuário poderá receber bonificação pelo primeiro descarte válido apenas uma vez.

## RN017 - Valor do bônus

Valor será configurável e permanece pendente até definição da economia de recompensas.

## RN021 - Descarte controlado

O registro de pesagem pelo aplicativo exige uma estação ativa e passa pelas
validações de material, quantidade mínima e pontuação antes de gerar créditos.

## RN022 - Resgate condicionado ao saldo

Recompensa somente poderá ser resgatada com saldo suficiente.

## RN023 - Débito no resgate

Confirmação do resgate debita o custo da recompensa.

## RN024 - Irreversibilidade pelo usuário

Após emissão do voucher, usuário não poderá cancelar o resgate por conta própria.

## RN025 - Validade do voucher

Voucher deverá respeitar sua data de validade.

## RN026 - Expiração

Voucher expirado não poderá ser utilizado.

## RN027 - Sem devolução automática

Expiração não devolve pontos automaticamente.

## RN028 - Estorno excepcional

Administrador poderá realizar estorno excepcional mediante justificativa.

Todo estorno deverá:

- possuir justificativa;
- gerar movimentação no extrato;
- ser rastreável.

## RN029 - Ranking mensal

Ranking utiliza período mensal.

## RN030 - Ranking por pontos conquistados

Ranking não utiliza saldo atual.

## RN031 - Resgates não reduzem ranking

Gastar pontos não reduz pontuação já conquistada no ranking daquele mês.

## RN032 - Novo período

Novo mês inicia nova classificação sem apagar saldo da conta.

## RN033 - Identificação pública do ranking

Ranking apresenta primeiro nome + inicial do sobrenome.

Exemplo:

| Nome completo | Exibição no ranking |
| --- | --- |
| João da Silva | João S. |

CPF, e-mail e nome completo não devem ser exibidos por padrão.

## RN034 - Premiação Top 3

Primeiro, segundo e terceiro colocados poderão receber recompensas.

## RN035 - Premiações configuráveis

Prêmios mensais não deverão ficar fixos no código.

## RN036 - Administração de recompensas

Somente administradores autorizados gerenciam recompensas no MVP.

## RN037 - Administração de parcerias

Parcerias são administradas pela equipe EcoDrop.

## RN038 - Parceiros sem painel

Empresas parceiras não possuirão painel próprio no MVP.

## RN039 - Valores comerciais configuráveis

Custos das recompensas, pontuação dos materiais, bônus e premiações deverão ser configuráveis quando aplicável.
