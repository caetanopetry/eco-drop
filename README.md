# EcoDrop

EcoDrop é uma plataforma de software para incentivar e gerenciar a coleta seletiva por meio de estações de descarte, pontuação por materiais recicláveis e resgate de recompensas.

## Sobre o projeto

O EcoDrop propõe um ecossistema em que usuários cadastrados utilizam estações EcoDrop, realizam descartes de materiais recicláveis, acumulam pontos conforme o tipo e a quantidade descartada e usam esses pontos para obter recompensas.

O conceito original considera uma estação física inteligente, com possibilidade futura de integração com balança, câmera, sensores e inteligência artificial. Nesta fase, porém, o foco é exclusivamente o desenvolvimento dos sistemas de software.

## Problema

A coleta seletiva depende de engajamento contínuo, rastreabilidade das entregas e estímulos claros para participação. Sem uma plataforma de apoio, torna-se mais difícil conectar descarte, acompanhamento, recompensa e gestão operacional.

## Proposta

A proposta do EcoDrop é oferecer uma plataforma web responsiva com:

- cadastro e autenticação de usuários;
- consulta de estações EcoDrop;
- identificação em estação por QR Code temporário;
- registro controlado de descartes simulados no MVP;
- cálculo e extrato de pontos;
- ranking mensal;
- catálogo de recompensas, resgates e vouchers;
- painel administrativo para gestão das principais entidades.

## MVP

O MVP deverá demonstrar o ciclo principal do EcoDrop: criação de conta, login, seleção de estação, associação por QR Code temporário, descarte simulado controlado pela interface da estação, crédito de pontos, histórico, extrato, ranking mensal, consulta de recompensas, resgate e emissão de voucher quando aplicável.

Enquanto não houver hardware real, a estação será representada por uma interface web com modo de demonstração/simulação controlado. O usuário comum não deverá autodeclarar descartes livremente pelo aplicativo para gerar pontos.

## Funcionalidades planejadas

- Aplicação web responsiva para usuários.
- Interface web da estação EcoDrop.
- Painel administrativo.
- Cadastro, autenticação, logout, perfil e recuperação de acesso.
- Consulta, detalhes e mapa de estações.
- Sessões temporárias de estação e QR Code com validade de 2 minutos.
- Registro simulado de descartes com material e quantidade.
- Configuração de materiais e pontos por unidade.
- Saldo e extrato de pontos.
- Histórico de descartes.
- Ranking mensal e premiação dos três primeiros colocados.
- Recompensas, resgates e vouchers.
- Administração de usuários, estações, materiais, recompensas, parceiros e premiações.

## Estado atual

**Fase 1 implementada:** estrutura base, configuração centralizada, conexão MySQL,
arquitetura em camadas, layout global/componentes e autenticação completa
(cadastro, login, logout, sessão, controle de acesso por perfil).

Fases seguintes (estações/QR Code/descarte/pontos, histórico/ranking,
recompensas/vouchers, Admin completo, refinamento) ainda não foram implementadas —
ver `docs/decisoes/pendencias.md` e a seção "Próximos passos" abaixo.

## Como executar localmente (XAMPP)

1. Copie a pasta do projeto para `C:\xampp\htdocs\EcoDrop`.
2. Crie o banco: abra o phpMyAdmin (ou o cliente MySQL de sua preferência) e
   execute, nesta ordem:
   - `backend/database/schema.sql`
   - `backend/database/seed.sql`
   Para bancos já existentes, não execute novamente o `schema.sql`: aplique,
   uma única vez e em ordem cronológica, cada migração ainda pendente em
   `backend/database/migrations/`. Em particular, a migração
   `20261002_add_fotos_to_usuarios.sql` adiciona as colunas de fotos usadas
   pelo perfil e pelos fluxos de login.
3. Instale as dependências na raiz do projeto com `composer install`.
   O carregamento do `.env` depende de `vlucas/phpdotenv`, instalado em `vendor/`.
   Se o Composer informar que falta suporte a ZIP, habilite `extension=zip` no
   `php.ini` usado pelo PHP da linha de comando.
4. Se ainda não existir, copie `.env.example` para `.env` e configure
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS` (esta última pode ser
   vazia no XAMPP). As credenciais do banco são lidas do `.env`, não de
   `backend/config/config.php`. Mantenha o `.env` fora do controle de versão.
5. Inicie o Apache e o MySQL pelo painel do XAMPP.
6. Acesse `http://localhost/EcoDrop/`, ajustando a porta conforme o Apache.
   Por exemplo, com `Listen 9999`, use `http://localhost:9999/EcoDrop/`.

### Usuários de teste (criados pelo seed)

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | `admin@ecodrop.local` | `EcoDrop@123` |
| Usuário comum | `usuario@ecodrop.local` | `Usuario@123` |

### Rotas disponíveis nesta fase

As URLs institucionais são encaminhadas pelo `.htaccess` ao controlador
principal, enquanto o conteúdo permanece em `frontend/pages/institucional/`.

- `/` — página institucional
- `/sobre/` — proposta e estágio atual do EcoDrop
- `/perguntas-frequentes/` — dúvidas sobre a demonstração e a conta
- `/contato/` — orientações e canal de atendimento
- `/termos-de-uso/` — condições da versão demonstrativa
- `/politica-de-privacidade/` — dados, cookies e informações de privacidade
- `/app/cadastro/` — criar conta
- `/app/login/` — entrar
- `/app/inicio/` — dashboard do usuário (protegida)
- `/app/logout/` — encerrar sessão
- `/admin/login/` — acesso administrativo
- `/admin/dashboard/` — dashboard administrativo (protegida, papel `admin`)

## Próximos passos recomendados

As páginas públicas compartilham cabeçalho e rodapé. Para publicar o e-mail de
atendimento, defina `contato.email` em `backend/config/config.php`, conforme o
exemplo. O formulário prepara a mensagem no aplicativo de e-mail do visitante,
que revisa e envia por lá; não há envio pelo servidor. Sem um e-mail válido,
o formulário fica desabilitado com o aviso “Envio em breve”. Os cartões de
WhatsApp e Instagram usam `contato.whatsapp` (código do país, DDD e número) e
`contato.instagram` (nome do perfil); canais não configurados exibem “Em breve”.
Os textos institucionais
descrevem a demonstração atual. A identificação do responsável pelo tratamento,
o canal de privacidade e os prazos de retenção precisam ser completados antes da
operação pública.

1. FASE 2 — estações, sessão temporária + QR Code, fluxo de descarte, cálculo de pontos.
2. FASE 3 — histórico, extrato, ranking mensal.
3. FASE 4 — recompensas, resgate, vouchers.
4. FASE 5 — Admin completo (usuários, estações, materiais, recompensas, parceiros, premiações).
5. FASE 6 — refinamento visual, responsividade, acessibilidade, estados de erro, segurança, testes.

## Documentação

A documentação essencial do projeto está organizada em:

- [`Visão geral`](docs/visao-geral.md)
- [`Escopo`](docs/escopo.md)
- [`Atores`](docs/atores.md)
- [`MVP`](docs/mvp.md)
- [`Requisitos funcionais`](docs/requisitos/requisitos-funcionais.md)
- [`Requisitos não funcionais`](docs/requisitos/requisitos-nao-funcionais.md)
- [`Regras de negócio`](docs/requisitos/regras-de-negocio.md)
- [`Arquitetura`](docs/arquitetura/README.md)
- [`Casos de uso`](docs/casos-de-uso/README.md) · [`Fluxos`](docs/fluxos/README.md)
- [`Decisões de implementação`](docs/decisoes/)

## Tecnologias

- Backend: PHP puro (sem framework), organizado em camadas (api/services/repositories/core).
- Frontend: HTML, CSS e JavaScript puro.
- Banco de dados: MySQL/MariaDB. Schema em `backend/database/schema.sql` (decisões em `docs/decisoes/banco-de-dados.md`).
- Provedor de mapas e serviços externos: PENDENTE (ver `docs/decisoes/pendencias.md`).

## Contexto

O EcoDrop está sendo desenvolvido no contexto do programa JEDI da Join.Valle.

## Roadmap resumido

1. Consolidar documentação inicial do produto.
2. Definir arquitetura e stack de implementação.
3. Modelar dados e persistência.
4. Desenvolver o MVP de software.
5. Avaliar integrações futuras com hardware, sensores, mapas e serviços externos.
