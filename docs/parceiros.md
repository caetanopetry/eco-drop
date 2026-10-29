# Administração de parceiros

A rota `/admin/parceiros/` exige uma conta administrativa e permite cadastrar e consultar organizações parceiras na tabela `parceiros`.

O formulário grava diretamente os campos `nome`, `descricao`, `contato` e `status`. A tela também apresenta totais por status e permite buscar por nome, descrição ou contato e filtrar parceiros ativos e inativos. O cadastro valida os limites definidos pelo schema, usa consulta preparada, exige token CSRF e retorna ao painel após salvar.

## Dados históricos de propostas

O fluxo de propostas deixou de fazer parte da interface administrativa. A tabela `propostas_parceria` e seu repositório foram mantidos para preservar os registros que possam existir em bancos já utilizados; nenhum dado ou estrutura foi removido.
