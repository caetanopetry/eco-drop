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

O projeto está em fase de especificação e organização da documentação inicial para desenvolvimento do MVP. Nenhum sistema de aplicação foi implementado nesta etapa.

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

## Tecnologias

Stack de implementação em definição.

Não há, neste momento, definição formal de framework, linguagem, banco de dados específico, provedor de mapas ou serviços externos. Essas decisões serão registradas em documentação própria quando forem tomadas.

## Contexto

O EcoDrop está sendo desenvolvido no contexto do programa JEDI da Join.Valle.

## Roadmap resumido

1. Consolidar documentação inicial do produto.
2. Definir arquitetura e stack de implementação.
3. Modelar dados e persistência.
4. Desenvolver o MVP de software.
5. Avaliar integrações futuras com hardware, sensores, mapas e serviços externos.
