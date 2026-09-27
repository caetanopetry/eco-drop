# Pendências

> Este arquivo não existia em `docs/decisoes/` no repositório. Consolida, num único
> lugar, as pendências já sinalizadas ao longo de `docs/escopo.md`,
> `docs/requisitos/*` e `docs/casos-de-uso/README.md`, além de pendências
> identificadas durante a implementação. Nenhum valor listado aqui foi inventado —
> o sistema foi construído para funcionar com esses campos vazios/configuráveis.

## Pendências de negócio (definição de valores)

| Item | Onde fica configurável hoje | Referência |
| --- | --- | --- |
| Pontos por unidade de cada material | `materiais.pontos_por_unidade` (NULL) | RN007, RN008, docs/escopo.md |
| Valor do bônus de primeiro descarte | `configuracoes.chave = 'bonus_primeiro_descarte_pontos'` (NULL) | RN016, RN017 |
| Custo em pontos de cada recompensa | `recompensas.custo_pontos` (NULL) | docs/escopo.md |
| Premiações do Top 3 do ranking mensal | `premiacoes_ranking` (tabela vazia) | RF040, RN034, RN035 |
| Parceiros comerciais específicos | `parceiros` (tabela vazia) | docs/escopo.md |
| Integração definitiva com Google Maps | Ainda não implementada; sem configuração ativa | RF008 |

**Enquanto esses valores não forem definidos, as funcionalidades dependentes deles
devem ser implementadas de forma que "pendente" seja um estado visível e tratado**
(ex.: card de recompensa sem preço definido não deve exibir um número
inventado), não um bloqueio silencioso.

## Pendências de fluxo/processo (ainda não detalhadas na documentação)

- Canal e etapas exatas de recuperação de acesso (UC003 / RF005).
- Credenciais aceitas e passos do método alternativo de identificação na estação,
  sem QR Code (UC008 / RF013).
- Critério que define se uma recompensa gera voucher ou não (UC016 / UC017).
- Quem solicita a utilização de um voucher e como ocorre a validação comercial
  completa junto ao parceiro (UC019 / FLUXO 08).
- Campos exatos de cadastro de uma estação além de nome/localização (UC022).
- Quais ações administrativas exatas são permitidas sobre um usuário (UC021).
- Transições de status permitidas para cada entidade (UC027).

## Pendências técnicas registradas durante a implementação (Fase 1)

- `backend/config/config.php` está commitado apenas neste ambiente de
  desenvolvimento local (com credenciais padrão do XAMPP: `root` sem senha). Em
  qualquer outro ambiente, este arquivo deve ser recriado a partir de
  `config.example.php` e nunca deve conter credenciais reais de produção.
- HTTPS (RNF003) não se aplica a este ambiente de desenvolvimento local; é uma
  pendência para o ambiente de produção futuro.
- Auditoria (RNF017) está estruturada (`auditoria_administrativa`) mas ainda sem
  nenhuma tela ou endpoint que grave nela — será usada a partir da Fase 5/6
  (estornos administrativos, UC020).
