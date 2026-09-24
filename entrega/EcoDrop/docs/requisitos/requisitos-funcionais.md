# Requisitos funcionais

## RF001 - Cadastro de usuário

| Campo | Valor |
| --- | --- |
| Descrição | O sistema deverá permitir a criação de conta com nome, e-mail, CPF e senha. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN014`](regras-de-negocio.md#rn014--unicidade-de-e-mail), [`RN015`](regras-de-negocio.md#rn015--unicidade-de-cpf) |

## RF002 - Autenticação

| Campo | Valor |
| --- | --- |
| Descrição | O sistema deverá permitir autenticação de usuários cadastrados. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |
| Observações | Senhas não poderão ser armazenadas em texto puro. |

## RF003 - Logout

| Campo | Valor |
| --- | --- |
| Descrição | O sistema deverá permitir encerramento da sessão autenticada. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |

## RF004 - Perfil

| Campo | Valor |
| --- | --- |
| Descrição | O usuário deverá poder consultar e alterar as informações permitidas de seu perfil. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Importante |
| Observações | CPF não deverá ser exibido publicamente. |

## RF005 - Recuperação de acesso

| Campo | Valor |
| --- | --- |
| Descrição | O sistema deverá possuir mecanismo de recuperação de acesso. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Importante |

## RF006 - Consulta de estações

| Campo | Valor |
| --- | --- |
| Descrição | O usuário deverá poder consultar estações EcoDrop cadastradas. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |

## RF007 - Detalhes da estação

| Campo | Valor |
| --- | --- |
| Descrição | O usuário deverá poder consultar informações de uma estação. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |

## RF008 - Mapa de estações

| Campo | Valor |
| --- | --- |
| Descrição | O usuário deverá poder visualizar as estações através de mapa. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Importante |
| Observações | Há intenção de integração com Google Maps, mas a implementação exata está pendente. |

## RF009 - Gerenciamento de estações

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder cadastrar, consultar, alterar e desativar estações. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |

## RF010 - Criação de sessão da estação

| Campo | Valor |
| --- | --- |
| Descrição | A estação deverá poder iniciar sessão temporária de utilização. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN020`](regras-de-negocio.md#rn020--associação-de-sessão) |

## RF011 - Geração de QR Code

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá gerar QR Code temporário para identificação. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN018`](regras-de-negocio.md#rn018--qr-code-temporário), [`RN019`](regras-de-negocio.md#rn019--qr-code-sem-dados-sensíveis) |

## RF012 - Identificação por QR Code

| Campo | Valor |
| --- | --- |
| Descrição | Usuário autenticado deverá poder associar-se à sessão através do QR Code. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN020`](regras-de-negocio.md#rn020--associação-de-sessão) |

## RF013 - Identificação alternativa

| Campo | Valor |
| --- | --- |
| Descrição | Deverá existir método alternativo por credenciais. |
| Ator relacionado | AT01 - Usuário, AT03 - Estação EcoDrop |
| Prioridade | Importante |
| Observações | Detalhamento do fluxo alternativo permanece pendente. |

## RF014 - Expiração de QR Code

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá invalidar QR Code após 2 minutos. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN018`](regras-de-negocio.md#rn018--qr-code-temporário) |

## RF015 - Registro de descarte

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá permitir registrar descarte associado ao usuário e estação. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN011`](regras-de-negocio.md#rn011--crédito-após-validação), [`RN021`](regras-de-negocio.md#rn021--descarte-controlado) |

## RF016 - Registro do material

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá registrar o material descartado. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN001`](regras-de-negocio.md#rn001--materiais-aceitos) |

## RF017 - Registro da quantidade

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá registrar a quantidade e unidade correspondentes. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN002`](regras-de-negocio.md#rn002--unidade-dos-sólidos), [`RN003`](regras-de-negocio.md#rn003--unidade-do-óleo) |

## RF018 - Validação de quantidade mínima

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá validar os limites mínimos definidos para cada material. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN004`](regras-de-negocio.md#rn004--quantidade-mínima-dos-sólidos), [`RN005`](regras-de-negocio.md#rn005--quantidade-mínima-do-óleo) |

## RF019 - Simulação de descarte

| Campo | Valor |
| --- | --- |
| Descrição | Interface da estação deverá permitir simular material e quantidade no MVP. |
| Ator relacionado | AT03 - Estação EcoDrop |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN021`](regras-de-negocio.md#rn021--descarte-controlado) |

## RF020 - Histórico de descartes

| Campo | Valor |
| --- | --- |
| Descrição | Usuário deverá poder consultar seus descartes. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |

## RF021 - Consulta administrativa de descartes

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder consultar descartes registrados. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |

## RF022 - Cálculo de pontos

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá calcular automaticamente os pontos do descarte. |
| Ator relacionado | Sistema |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN006`](regras-de-negocio.md#rn006--pontuação-por-quantidade), [`RN007`](regras-de-negocio.md#rn007--pontuação-específica-por-material), [`RN008`](regras-de-negocio.md#rn008--configuração-administrativa-da-pontuação) |

## RF023 - Arredondamento de pontos

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá converter resultados decimais em pontos inteiros através de arredondamento matemático. |
| Ator relacionado | Sistema |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN009`](regras-de-negocio.md#rn009--pontos-inteiros), [`RN010`](regras-de-negocio.md#rn010--arredondamento) |

## RF024 - Saldo de pontos

| Campo | Valor |
| --- | --- |
| Descrição | Usuário deverá poder consultar saldo. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN012`](regras-de-negocio.md#rn012--pontos-não-expiram), [`RN013`](regras-de-negocio.md#rn013--extrato) |

## RF025 - Extrato de pontos

| Campo | Valor |
| --- | --- |
| Descrição | Usuário deverá poder consultar movimentações de pontos. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN013`](regras-de-negocio.md#rn013--extrato) |

## RF026 - Configuração de pontuação

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder configurar pontos por unidade de cada material. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |
| Regras relacionadas | [`RN008`](regras-de-negocio.md#rn008--configuração-administrativa-da-pontuação), [`RN039`](regras-de-negocio.md#rn039--valores-comerciais-configuráveis) |

## RF027 - Gerenciamento de materiais

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder administrar materiais e suas configurações. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |

## RF028 - Bônus de primeiro descarte

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá suportar bonificação única pelo primeiro descarte válido. |
| Ator relacionado | Sistema |
| Prioridade | Importante |
| Regras relacionadas | [`RN016`](regras-de-negocio.md#rn016--bônus-de-primeiro-descarte), [`RN017`](regras-de-negocio.md#rn017--valor-do-bônus) |

## RF029 - Catálogo de recompensas

| Campo | Valor |
| --- | --- |
| Descrição | Usuário deverá poder consultar recompensas disponíveis. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |

## RF030 - Gerenciamento de recompensas

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder cadastrar, alterar, ativar, desativar e administrar recompensas. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |
| Regras relacionadas | [`RN036`](regras-de-negocio.md#rn036--administração-de-recompensas) |

## RF031 - Resgate de recompensa

| Campo | Valor |
| --- | --- |
| Descrição | Usuário deverá poder resgatar recompensa quando possuir saldo suficiente. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN022`](regras-de-negocio.md#rn022--resgate-condicionado-ao-saldo), [`RN023`](regras-de-negocio.md#rn023--débito-no-resgate), [`RN024`](regras-de-negocio.md#rn024--irreversibilidade-pelo-usuário) |

## RF032 - Emissão de voucher

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá gerar voucher quando aplicável. |
| Ator relacionado | Sistema |
| Prioridade | Essencial para MVP |

## RF033 - Consulta de vouchers

| Campo | Valor |
| --- | --- |
| Descrição | Usuário deverá poder consultar seus vouchers. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |

## RF034 - Controle de validade do voucher

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá controlar expiração dos vouchers. |
| Ator relacionado | Sistema |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN025`](regras-de-negocio.md#rn025--validade-do-voucher), [`RN026`](regras-de-negocio.md#rn026--expiração), [`RN027`](regras-de-negocio.md#rn027--sem-devolução-automática) |

## RF035 - Utilização de voucher

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá suportar alteração do voucher para utilizado. |
| Ator relacionado | Sistema |
| Prioridade | Importante |
| Observações | A validação comercial completa de vouchers é possibilidade futura. |

## RF036 - Estorno administrativo

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder realizar estorno excepcional com justificativa. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |
| Regras relacionadas | [`RN028`](regras-de-negocio.md#rn028--estorno-excepcional) |

## RF037 - Ranking mensal

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá gerar classificação mensal. |
| Ator relacionado | Sistema |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN029`](regras-de-negocio.md#rn029--ranking-mensal), [`RN030`](regras-de-negocio.md#rn030--ranking-por-pontos-conquistados), [`RN031`](regras-de-negocio.md#rn031--resgates-não-reduzem-ranking), [`RN032`](regras-de-negocio.md#rn032--novo-período) |

## RF038 - Posição do usuário

| Campo | Valor |
| --- | --- |
| Descrição | Usuário deverá poder visualizar sua posição no ranking. |
| Ator relacionado | AT01 - Usuário |
| Prioridade | Essencial para MVP |

## RF039 - Privacidade do ranking

| Campo | Valor |
| --- | --- |
| Descrição | Ranking deverá apresentar primeiro nome e inicial do sobrenome. |
| Ator relacionado | Sistema |
| Prioridade | Essencial para MVP |
| Regras relacionadas | [`RN033`](regras-de-negocio.md#rn033--identificação-pública-do-ranking) |

## RF040 - Premiação Top 3

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá permitir definir premiações para primeiro, segundo e terceiro colocados de cada período. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |
| Regras relacionadas | [`RN034`](regras-de-negocio.md#rn034--premiação-top-3), [`RN035`](regras-de-negocio.md#rn035--premiações-configuráveis) |

## RF041 - Painel administrativo

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá possuir área restrita para administradores. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |

## RF042 - Gerenciamento de usuários

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder consultar e administrar usuários conforme permissões. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |

## RF043 - Gerenciamento de parceiros

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder registrar/administrar informações necessárias sobre empresas parceiras. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |
| Regras relacionadas | [`RN037`](regras-de-negocio.md#rn037--administração-de-parcerias), [`RN038`](regras-de-negocio.md#rn038--parceiros-sem-painel) |

## RF044 - Gerenciamento de premiações

| Campo | Valor |
| --- | --- |
| Descrição | Administrador deverá poder configurar premiações mensais do ranking. |
| Ator relacionado | AT02 - Administrador |
| Prioridade | Importante |
| Regras relacionadas | [`RN035`](regras-de-negocio.md#rn035--premiações-configuráveis) |

## RF045 - Controle de status

| Campo | Valor |
| --- | --- |
| Descrição | Sistema deverá suportar estados das entidades relevantes, como contas, estações, recompensas e vouchers. |
| Ator relacionado | Sistema |
| Prioridade | Importante |
