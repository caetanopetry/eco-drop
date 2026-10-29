# Requisitos não funcionais

## RNF001 - Responsividade

| Campo | Valor |
| --- | --- |
| Descrição | Interfaces destinadas ao usuário deverão funcionar adequadamente em smartphones, tablets e computadores. |
| Prioridade | Essencial para MVP |

## RNF002 - Segurança de senhas

| Campo | Valor |
| --- | --- |
| Descrição | Senhas deverão ser armazenadas utilizando hash seguro e nunca texto puro. |
| Prioridade | Essencial para MVP |

## RNF003 - Comunicação segura

| Campo | Valor |
| --- | --- |
| Descrição | Ambiente de produção futuro deverá utilizar HTTPS. |
| Prioridade | Importante |

## RNF004 - Controle de acesso

| Campo | Valor |
| --- | --- |
| Descrição | Funcionalidades administrativas deverão ser restritas a usuários autorizados. |
| Prioridade | Essencial para MVP |

## RNF005 - Validação no servidor

| Campo | Valor |
| --- | --- |
| Descrição | Operações sensíveis deverão ser validadas no backend. |
| Prioridade | Essencial para MVP |

## RNF006 - Integridade das transações

| Campo | Valor |
| --- | --- |
| Descrição | Alterações de pontos, resgates e estornos deverão preservar consistência mesmo em caso de falhas. |
| Prioridade | Essencial para MVP |

## RNF007 - Rastreabilidade

| Campo | Valor |
| --- | --- |
| Descrição | Movimentações relevantes deverão possuir origem identificável. |
| Prioridade | Essencial para MVP |

## RNF008 - Expiração de tokens temporários

| Campo | Valor |
| --- | --- |
| Descrição | Tokens temporários de recuperação de acesso deverão possuir expiração. |
| Prioridade | Essencial para MVP |

## RNF009 - Usabilidade

| Campo | Valor |
| --- | --- |
| Descrição | Fluxos principais deverão possuir interface simples e compreensível. |
| Prioridade | Importante |

## RNF010 - Manutenibilidade

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá possuir organização modular. |
| Prioridade | Importante |

## RNF011 - Extensibilidade

| Campo | Valor |
| --- | --- |
| Descrição | Arquitetura deverá permitir futura integração com hardware sem tornar hardware obrigatório no MVP. |
| Prioridade | Importante |

## RNF012 - Persistência

| Campo | Valor |
| --- | --- |
| Descrição | Dados persistentes deverão utilizar banco de dados relacional. |
| Prioridade | Importante |
| Observações | A modelagem e a escolha específica de banco de dados ainda não foram definidas nesta etapa. |

## RNF013 - Privacidade

| Campo | Valor |
| --- | --- |
| Descrição | Dados pessoais como CPF e e-mail não deverão ser expostos publicamente. |
| Prioridade | Essencial para MVP |

## RNF014 - Proteção de dados

| Campo | Valor |
| --- | --- |
| Descrição | A implementação futura deverá considerar requisitos aplicáveis de proteção de dados pessoais, especialmente devido ao armazenamento de CPF. |
| Prioridade | Importante |

## RNF015 - Consistência de pontuação

| Campo | Valor |
| --- | --- |
| Descrição | Crédito/débito de pontos não deverá depender de valores enviados livremente pelo cliente. |
| Prioridade | Essencial para MVP |

## RNF016 - Unicidade

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá garantir unicidade de e-mail e CPF conforme regras de negócio. |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN014`](regras-de-negocio.md#rn014--unicidade-de-e-mail), [`RN015`](regras-de-negocio.md#rn015--unicidade-de-cpf) |

## RNF017 - Auditabilidade

| Campo | Valor |
| --- | --- |
| Descrição | Operações administrativas sensíveis, especialmente estornos, deverão possuir registros rastreáveis. |
| Prioridade | Importante |
| Regras relacionadas | [`RN028`](regras-de-negocio.md#rn028--estorno-excepcional) |
