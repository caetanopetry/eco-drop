# Casos de uso

Este documento descreve os comportamentos do MVP a partir dos requisitos e regras já aprovados. A estação é uma interface de software com simulação controlada; hardware físico está fora do MVP.

## UC001 — Cadastrar usuário

| Item | Descrição |
| --- | --- |
| Identificador | UC001 |
| Objetivo | Criar uma conta de usuário. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Usuário ainda não possui conta com o e-mail ou CPF informado. |
| Pós-condições | Conta é criada com nome, e-mail, CPF e senha. |
| Fluxo principal | 1. Usuário informa os dados de cadastro. 2. Sistema verifica unicidade de e-mail e CPF. 3. Sistema cria a conta. |
| Fluxos alternativos | Usuário corrige os dados antes da confirmação. |
| Exceções | E-mail ou CPF já associado a uma conta: sistema não conclui o cadastro. |
| Regras | RN014, RN015 |
| RFs | RF001 |
| Observações | A forma de validação dos campos além da unicidade permanece PENDENTE. |

## UC002 — Autenticar usuário

| Item | Descrição |
| --- | --- |
| Identificador | UC002 |
| Objetivo | Permitir acesso de usuário cadastrado à plataforma. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Conta cadastrada. |
| Pós-condições | Sessão autenticada é iniciada. |
| Fluxo principal | 1. Usuário informa credenciais. 2. Sistema confere as credenciais. 3. Sistema autentica o usuário. |
| Fluxos alternativos | Usuário pode seguir para recuperação de acesso. |
| Exceções | Credenciais inválidas: sistema informa que a autenticação não foi concluída. |
| Regras | — |
| RFs | RF002 |
| Observações | Senhas não são armazenadas em texto puro (RNF002). |

## UC003 — Recuperar acesso

| Item | Descrição |
| --- | --- |
| Identificador | UC003 |
| Objetivo | Permitir recuperação de acesso à conta. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Conta cadastrada. |
| Pós-condições | Um link de redefinição de uso único é enviado ao e-mail cadastrado, quando houver conta ativa. |
| Fluxo principal | 1. Usuário informa o e-mail. 2. Sistema sempre apresenta uma resposta que não revela se a conta existe. 3. Para uma conta ativa, o sistema armazena o hash de um token temporário e envia o link por e-mail. 4. O usuário define uma nova senha. |
| Fluxos alternativos | — |
| Exceções | Dados insuficientes ou não reconhecidos impedem a conclusão da solicitação. |
| Regras | — |
| RFs | RF005 |
| Observações | O link expira em 60 minutos; o token é invalidado após a redefinição. A senha é armazenada com `password_hash()`. |

## UC004 — Consultar estações

| Item | Descrição |
| --- | --- |
| Identificador | UC004 |
| Objetivo | Consultar estações EcoDrop cadastradas. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Existem estações cadastradas. |
| Pós-condições | Usuário visualiza as estações disponíveis. |
| Fluxo principal | 1. Usuário solicita a consulta. 2. Sistema apresenta as estações cadastradas. |
| Fluxos alternativos | Usuário pode visualizar as estações em mapa. |
| Exceções | Não há estações disponíveis: sistema apresenta a ausência de resultados. |
| Regras | — |
| RFs | RF006, RF008 |
| Observações | A integração definitiva com mapas está PENDENTE. |

## UC005 — Visualizar estação

| Item | Descrição |
| --- | --- |
| Identificador | UC005 |
| Objetivo | Consultar informações de uma estação específica. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Estação cadastrada. |
| Pós-condições | Informações da estação são apresentadas. |
| Fluxo principal | 1. Usuário seleciona uma estação. 2. Sistema mostra seus detalhes. |
| Fluxos alternativos | Usuário retorna à consulta de estações. |
| Exceções | Estação indisponível ou não encontrada: sistema informa a condição. |
| Regras | — |
| RFs | RF007, RF045 |
| Observações | Os dados exibidos por estação permanecem PENDENTES. |

## UC009 — Registrar descarte

| Item | Descrição |
| --- | --- |
| Identificador | UC009 |
| Objetivo | Registrar descarte associado a usuário e estação. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Usuário autenticado; estação ativa e material disponível. |
| Pós-condições | Descarte válido é registrado e encaminhado à pontuação. |
| Fluxo principal | 1. Usuário seleciona estação e material em “Meus Descartes”. 2. Informa a quantidade. 3. Sistema valida e registra a pesagem. |
| Fluxos alternativos | — |
| Exceções | Estação inativa, material inválido ou quantidade abaixo do mínimo: não há crédito de pontos. |
| Regras | RN001, RN002, RN003, RN011, RN021 |
| RFs | RF015, RF016, RF017, RF019 |
| Observações | A sessão técnica vinculada ao registro é criada pelo sistema na mesma transação. |

## UC010 — Validar quantidade do descarte

| Item | Descrição |
| --- | --- |
| Identificador | UC010 |
| Objetivo | Verificar se a quantidade atende ao mínimo do material. |
| Ator principal | Sistema |
| Atores secundários | AT01 — Usuário |
| Pré-condições | Material, quantidade e unidade foram informados no aplicativo. |
| Pós-condições | Descarte é classificado como válido ou inválido para pontuação. |
| Fluxo principal | 1. Sistema identifica o material. 2. Aplica a unidade e o mínimo correspondentes. 3. Informa o resultado da validação. |
| Fluxos alternativos | Validação para sólido ou óleo usa o limite aplicável. |
| Exceções | Material ou unidade incompatível: validação não é aprovada. |
| Regras | RN001, RN002, RN003, RN004, RN005 |
| RFs | RF016, RF017, RF018 |
| Observações | Sólidos exigem 0,100 kg; óleo exige 0,500 L. |

## UC011 — Calcular e creditar pontos

| Item | Descrição |
| --- | --- |
| Identificador | UC011 |
| Objetivo | Calcular, arredondar e creditar os pontos de descarte válido. |
| Ator principal | Sistema |
| Atores secundários | AT01 — Usuário |
| Pré-condições | Descarte válido registrado; pontuação do material configurada. |
| Pós-condições | Pontos inteiros são creditados; extrato, histórico e ranking são atualizados. |
| Fluxo principal | 1. Sistema calcula pontos conforme material e quantidade. 2. Arredonda matematicamente para inteiro. 3. Credita os pontos. 4. Registra a movimentação e o histórico. 5. Considera os pontos no ranking mensal. |
| Fluxos alternativos | Bônus único de primeiro descarte pode ser aplicado se configurado. |
| Exceções | Descarte inválido não recebe crédito. Configuração indisponível impede a conclusão. |
| Regras | RN006 a RN013, RN016, RN017, RN021, RN029 a RN032, RN039 |
| RFs | RF022, RF023, RF024, RF025, RF028, RF037 |
| Observações | Valor do bônus de primeiro descarte está PENDENTE. |

## UC012 — Consultar histórico de descartes

| Item | Descrição |
| --- | --- |
| Identificador | UC012 |
| Objetivo | Consultar descartes do próprio usuário. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Usuário autenticado. |
| Pós-condições | Histórico de descartes é apresentado. |
| Fluxo principal | 1. Usuário solicita o histórico. 2. Sistema apresenta os descartes associados à conta. |
| Fluxos alternativos | — |
| Exceções | Não há descartes: sistema informa a ausência de registros. |
| Regras | RN021 |
| RFs | RF020 |
| Observações | — |

## UC013 — Consultar saldo e extrato de pontos

| Item | Descrição |
| --- | --- |
| Identificador | UC013 |
| Objetivo | Consultar saldo e movimentações de pontos. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Usuário autenticado. |
| Pós-condições | Saldo e extrato são apresentados. |
| Fluxo principal | 1. Usuário solicita saldo ou extrato. 2. Sistema mostra o saldo e as movimentações relevantes. |
| Fluxos alternativos | Usuário consulta somente saldo ou somente extrato. |
| Exceções | Não há movimentações: extrato é apresentado sem registros. |
| Regras | RN009, RN012, RN013 |
| RFs | RF024, RF025 |
| Observações | Pontos não expiram. |

## UC014 — Consultar ranking mensal

| Item | Descrição |
| --- | --- |
| Identificador | UC014 |
| Objetivo | Consultar classificação do período mensal e a posição do usuário. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Ranking mensal disponível. |
| Pós-condições | Ranking e posição do usuário são apresentados. |
| Fluxo principal | 1. Usuário solicita o ranking. 2. Sistema classifica pelos pontos ganhos no mês. 3. Sistema apresenta nomes protegidos e posição. |
| Fluxos alternativos | Usuário consulta novo período após mudança de mês. |
| Exceções | Sem pontuações no período: sistema apresenta a condição. |
| Regras | RN029 a RN033 |
| RFs | RF037, RF038, RF039 |
| Observações | Resgates não reduzem a pontuação do ranking. |

## UC015 — Consultar catálogo de recompensas

| Item | Descrição |
| --- | --- |
| Identificador | UC015 |
| Objetivo | Visualizar recompensas disponíveis. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Usuário acessa o catálogo. |
| Pós-condições | Recompensas disponíveis são apresentadas. |
| Fluxo principal | 1. Usuário solicita o catálogo. 2. Sistema apresenta recompensas disponíveis. |
| Fluxos alternativos | Usuário seleciona uma recompensa para iniciar resgate. |
| Exceções | Não há recompensas disponíveis: sistema informa a condição. |
| Regras | RN036, RN039 |
| RFs | RF029, RF045 |
| Observações | Custos e recompensas específicas estão PENDENTES. |

## UC016 — Resgatar recompensa

| Item | Descrição |
| --- | --- |
| Identificador | UC016 |
| Objetivo | Trocar pontos por uma recompensa disponível. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Usuário autenticado; recompensa disponível; saldo suficiente. |
| Pós-condições | Resgate é confirmado, pontos são debitados e o voucher é emitido quando aplicável. |
| Fluxo principal | 1. Usuário seleciona a recompensa. 2. Sistema verifica disponibilidade e saldo. 3. Usuário confirma o resgate. 4. Sistema debita os pontos. 5. Sistema emite voucher quando aplicável. |
| Fluxos alternativos | Resgate sem voucher: aplicabilidade permanece PENDENTE. |
| Exceções | Saldo insuficiente ou recompensa indisponível: resgate não é confirmado. |
| Regras | RN022, RN023, RN024, RN036, RN039 |
| RFs | RF031, RF032 |
| Observações | Após emissão do voucher, o usuário não cancela o resgate. |

## UC017 — Emitir voucher

| Item | Descrição |
| --- | --- |
| Identificador | UC017 |
| Objetivo | Gerar voucher associado a um resgate aplicável. |
| Ator principal | Sistema |
| Atores secundários | AT01 — Usuário |
| Pré-condições | Resgate confirmado e recompensa aplicável a voucher. |
| Pós-condições | Voucher com código único, QR Code, validade e status é disponibilizado. |
| Fluxo principal | 1. Sistema cria o voucher. 2. Define código único, QR Code, validade e status. 3. Associa o voucher ao usuário e ao resgate. |
| Fluxos alternativos | — |
| Exceções | Falha na emissão: a consistência entre resgate, débito e voucher deve ser preservada. |
| Regras | RN023 a RN027 |
| RFs | RF032, RF034, RF045 |
| Observações | Critério de aplicabilidade do voucher está PENDENTE. |

## UC018 — Consultar vouchers

| Item | Descrição |
| --- | --- |
| Identificador | UC018 |
| Objetivo | Consultar vouchers vinculados à própria conta. |
| Ator principal | AT01 — Usuário |
| Atores secundários | Sistema |
| Pré-condições | Usuário autenticado. |
| Pós-condições | Vouchers e respectivos status são apresentados. |
| Fluxo principal | 1. Usuário solicita seus vouchers. 2. Sistema apresenta os vouchers associados. |
| Fluxos alternativos | Usuário consulta um voucher específico. |
| Exceções | Não há vouchers: sistema informa a ausência de registros. |
| Regras | RN025 a RN027 |
| RFs | RF033, RF034, RF045 |
| Observações | — |

## UC019 — Utilizar voucher

| Item | Descrição |
| --- | --- |
| Identificador | UC019 |
| Objetivo | Registrar voucher como utilizado quando sua utilização for validada. |
| Ator principal | Sistema |
| Atores secundários | AT01 — Usuário; AT02 — Administrador, quando necessário |
| Pré-condições | Voucher existente, válido e não utilizado. |
| Pós-condições | Status do voucher passa para utilizado. |
| Fluxo principal | 1. É solicitada a utilização do voucher. 2. Sistema verifica sua validade e status. 3. Sistema registra o voucher como utilizado. |
| Fluxos alternativos | Administrador pode administrar vouchers quando necessário. |
| Exceções | Voucher expirado ou já utilizado: sistema não permite a utilização. |
| Regras | RN025, RN026 |
| RFs | RF034, RF035, RF045 |
| Observações | Ator que solicita a utilização e validação comercial completa estão PENDENTES; parceiros não possuem painel no MVP. |

## UC020 — Realizar estorno administrativo

| Item | Descrição |
| --- | --- |
| Identificador | UC020 |
| Objetivo | Efetuar estorno excepcional com justificativa e rastreabilidade. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado; operação que justifique o estorno. |
| Pós-condições | Estorno é registrado, gera movimentação no extrato e permanece rastreável. |
| Fluxo principal | 1. Administrador seleciona a operação. 2. Informa justificativa. 3. Sistema registra o estorno e a movimentação correspondente. |
| Fluxos alternativos | — |
| Exceções | Ausência de justificativa ou falta de autorização: estorno não é realizado. |
| Regras | RN013, RN028 |
| RFs | RF036 |
| Observações | Consistência e auditabilidade são obrigatórias (RNF006, RNF017). |

## UC021 — Administrar usuários

| Item | Descrição |
| --- | --- |
| Identificador | UC021 |
| Objetivo | Consultar e administrar usuários conforme permissões. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado no painel administrativo. |
| Pós-condições | Ação administrativa permitida é registrada/aplicada. |
| Fluxo principal | 1. Administrador acessa usuários. 2. Consulta ou executa ação permitida. 3. Sistema aplica a ação. |
| Fluxos alternativos | Administrador apenas consulta usuários. |
| Exceções | Falta de autorização: acesso é negado. |
| Regras | RN014, RN015 |
| RFs | RF041, RF042 |
| Observações | Quais ações administrativas são permitidas permanece PENDENTE. |

## UC022 — Administrar estações

| Item | Descrição |
| --- | --- |
| Identificador | UC022 |
| Objetivo | Cadastrar, consultar, alterar e desativar estações. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado. |
| Pós-condições | Cadastro ou estado da estação é atualizado. |
| Fluxo principal | 1. Administrador acessa estações. 2. Escolhe cadastrar, consultar, alterar ou desativar. 3. Sistema aplica a operação. |
| Fluxos alternativos | Administrador mantém a estação sem alteração. |
| Exceções | Falta de autorização ou dados insuficientes: operação não é concluída. |
| Regras | — |
| RFs | RF009, RF041, RF045 |
| Observações | Campos de cadastro permanecem PENDENTES. |

## UC023 — Administrar materiais e pontuação

| Item | Descrição |
| --- | --- |
| Identificador | UC023 |
| Objetivo | Administrar materiais e configurar pontos por unidade. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado. |
| Pós-condições | Configuração de material ou pontuação é atualizada. |
| Fluxo principal | 1. Administrador acessa materiais. 2. Administra configurações. 3. Define ou altera pontos por unidade. 4. Sistema guarda a configuração. |
| Fluxos alternativos | Administrador apenas consulta configurações. |
| Exceções | Configuração inválida ou não autorizada: alteração não é aplicada. |
| Regras | RN001 a RN008, RN039 |
| RFs | RF026, RF027, RF041 |
| Observações | Valores exatos de pontos estão PENDENTES. |

## UC024 — Administrar recompensas

| Item | Descrição |
| --- | --- |
| Identificador | UC024 |
| Objetivo | Cadastrar, alterar, ativar, desativar e administrar recompensas. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado. |
| Pós-condições | Recompensa ou seu status é atualizado. |
| Fluxo principal | 1. Administrador acessa recompensas. 2. Executa a operação desejada. 3. Sistema aplica a alteração. |
| Fluxos alternativos | Administrador consulta recompensa sem alteração. |
| Exceções | Falta de autorização ou dados insuficientes: operação não é concluída. |
| Regras | RN036, RN039 |
| RFs | RF030, RF041, RF045 |
| Observações | Custos das recompensas estão PENDENTES. |

## UC025 — Administrar parceiros

| Item | Descrição |
| --- | --- |
| Identificador | UC025 |
| Objetivo | Registrar e administrar informações de parceiros. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado. |
| Pós-condições | Informações necessárias de parceiros são administradas. |
| Fluxo principal | 1. Administrador acessa parceiros. 2. Registra ou administra informações necessárias. 3. Sistema aplica a operação. |
| Fluxos alternativos | Administrador apenas consulta parceiros. |
| Exceções | Falta de autorização: operação não é concluída. |
| Regras | RN037, RN038 |
| RFs | RF043, RF041 |
| Observações | Parceiro não é ator e não possui painel no MVP; parceiros específicos e dados administrados estão PENDENTES. |

## UC026 — Administrar premiações

| Item | Descrição |
| --- | --- |
| Identificador | UC026 |
| Objetivo | Configurar premiações mensais para os três primeiros do ranking. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado. |
| Pós-condições | Premiações mensais são configuradas. |
| Fluxo principal | 1. Administrador acessa premiações. 2. Define premiações do período. 3. Sistema registra a configuração. |
| Fluxos alternativos | Administrador altera configuração existente. |
| Exceções | Falta de autorização ou configuração inválida: alteração não é concluída. |
| Regras | RN034, RN035, RN039 |
| RFs | RF040, RF044, RF041 |
| Observações | Premiações específicas estão PENDENTES. |

## UC027 — Administrar status dos recursos

| Item | Descrição |
| --- | --- |
| Identificador | UC027 |
| Objetivo | Administrar status de recursos relevantes quando a operação administrativa o permitir. |
| Ator principal | AT02 — Administrador |
| Atores secundários | Sistema |
| Pré-condições | Administrador autorizado; recurso existente. |
| Pós-condições | Status aplicável é atualizado ou consultado. |
| Fluxo principal | 1. Administrador acessa o recurso. 2. Consulta ou altera o status permitido. 3. Sistema registra o estado resultante. |
| Fluxos alternativos | Status pode ser alterado automaticamente pelo sistema, como expiração de voucher ou QR Code. |
| Exceções | Transição não permitida ou falta de autorização: status não é alterado. |
| Regras | RN025, RN026, RN036 |
| RFs | RF045, RF009, RF030, RF034, RF035 |
| Observações | Estados, transições e quais status podem ser alterados manualmente estão PENDENTES. |

## Rastreabilidade

| Casos de uso | RFs | RNs principais |
| --- | --- | --- |
| UC001 | RF001 | RN014, RN015 |
| UC002–UC003 | RF002, RF005 | — |
| UC004–UC005 | RF006–RF008, RF045 | — |
| UC009–UC011 | RF015–RF019, RF022–RF025, RF028, RF037 | RN001–RN013, RN016–RN021, RN029–RN032, RN039 |
| UC012–UC013 | RF020, RF024–RF025 | RN009, RN012, RN013, RN021 |
| UC014 | RF037–RF039 | RN029–RN033 |
| UC015–UC019 | RF029–RF035, RF045 | RN022–RN027, RN036, RN039 |
| UC020 | RF036 | RN013, RN028 |
| UC021–UC027 | RF009, RF026–RF027, RF030, RF040–RF045 | RN001–RN008, RN025–RN026, RN034–RN039 |

Os casos também se relacionam aos requisitos não funcionais aplicáveis, especialmente RNF004 e RNF005 para operações restritas e sensíveis, RNF006 e RNF007 para transações e registros, RNF008 para tokens de recuperação e RNF013 para privacidade.
