# Administração de parceiros

A rota `/admin/parceiros/` exige uma conta administrativa. Mantém propostas candidatas em `propostas_parceria`, separadas dos parceiros já cadastrados em `parceiros`.

É possível registrar propostas recebidas por outros canais, pesquisar organização, responsável, e-mail ou cidade, filtrar por status e avaliar com observações internas. A última avaliação registra administrador e data. Aprovar não cria nem ativa um parceiro e não envia mensagens.

Campos obrigatórios: organização, responsável, e-mail, telefone, cidade/UF, segmento, tipo de parceria e descrição. Propostas e observações aceitam até 5.000 caracteres. O servidor valida campos e status; alterações exigem sessão administrativa e CSRF válido.

## Instalação e testes

Em bancos existentes, aplicar `backend/database/migrations/20261016_create_propostas_parceria.sql`. Instalações novas já incluem a tabela em `schema.sql`.

A verificação é manual: abra `/admin/parceiros/` com uma conta administrativa ativa, envie uma proposta pelo formulário e confira o registro na listagem. A avaliação também pode ser feita na própria tela.

## Próxima etapa: Seja um Parceiro

O formulário público ainda não foi criado. Poderá usar `PropostasParceriaRepository::receber()` para enviar os mesmos campos e iniciar propostas no status `nova`. A rota pública deverá incluir validação, proteção contra abuso, CSRF e informações sobre uso dos dados. As observações internas e avaliações devem permanecer exclusivas da área administrativa.
