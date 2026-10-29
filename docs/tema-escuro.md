# Tema claro e escuro do EcoDrop

O projeto usa PHP, HTML, CSS e JavaScript sem framework de frontend. O tema claro continua sendo o padrão do CSS. O tema escuro usa `data-theme="dark"` no elemento `html`. As medidas, grades, fontes, breakpoints e textos existentes foram preservados. Foi acrescentado somente o controle necessário à alternância, sem mudanças nas rotas ou nas regras de negócio.

## Arquivos

| Arquivo | Alteração |
| --- | --- |
| `frontend/assets/css/variables.css` | Tokens semânticos dos dois temas e aliases compatíveis com os nomes originais. Centraliza cores, transparências e cores de sombras. |
| `frontend/assets/css/theme.css` | Botão lua/sol, estados escuros por componente, foco visível de 2px, transições de 200ms condicionadas à preferência de movimento e apresentação da marca. |
| `frontend/assets/js/theme.js` | Aplicação antes da renderização, preferência do sistema, persistência em `ecodrop-theme`, sincronização entre abas, rótulos acessíveis e tolerância a armazenamento bloqueado. |
| `frontend/assets/css/global.css` | Seleção, destaque de toque e cores globais usam tokens. |
| `frontend/assets/css/components.css` | Botões, formulários e painéis compartilhados usam tokens. |
| `frontend/assets/css/app.css` | Sidebar, perfil, hero, cards, ranking, recompensas, divisórias e sombras usam tokens. |
| `frontend/assets/css/conta.css` | Cabeçalho, upload de foto, navegação e detalhes usam tokens; conserva o estilo da pílula junto ao novo botão. |
| `frontend/assets/css/admin.css` | Navegação, estados do menu e marca usam tokens. |
| `frontend/assets/css/admin-usuarios.css` | Indicadores, avisos, gráficos, tags, inputs e modal usam tokens. |
| `frontend/assets/css/auth.css` | Cores do painel e de sua decoração usam tokens. |
| `frontend/assets/css/site.css` | Cabeçalho, materiais, hero, convite e estados de interação usam tokens. |
| `frontend/assets/css/institucional.css` | Cards, linha do tempo, documentos e FAQ usam tokens. |
| `frontend/assets/css/contato.css` | Canais, formulário e controles desabilitados usam tokens. |
| `frontend/assets/css/hover-footer.css` | Cores, divisórias e sombras do rodapé usam tokens. |
| `frontend/components/theme/head.php` | Carrega o script síncrono antes dos estilos. |
| `frontend/components/theme/toggle.php` | Botão compartilhado com SVG decorativo e `aria-label` atualizado. |
| `frontend/components/app/perfil.php` | Posiciona a alternância junto à pílula do usuário. |
| `frontend/components/navbar/navbar-site.php` | Inclui a alternância no cabeçalho público. |
| `frontend/components/brand/marca.php` | Usa a imagem original `logo-e-nome.png` nos dois temas, preservando o símbolo e a tipografia. |
| `frontend/pages/app/{inicio,saques,extrato,conta}.php` | Recursos do tema e CSS versionados; botão no cabeçalho de Conta. |
| `frontend/pages/admin/{dashboard,usuarios,login}.php` | Recursos do tema e botão de alternância. |
| `frontend/pages/auth/{layout,google-completar,esqueci-senha,redefinir-senha}.php` | Recursos do tema e botão de alternância, incluindo as telas recebidas do repositório. |
| `frontend/pages/inicio.php`, `frontend/pages/institucional/layout.php` | Recursos compartilhados e CSS versionados nas páginas públicas. |
| `tests/theme.test.cjs` | Testes de preferência, persistência, acessibilidade do controle, sincronização, tokens e contraste. |

## Paleta e contraste

A paleta escura solicitada foi mantida. Os pares de texto principal, secundário, menu e destaque sobre os fundos principais, de cards, menus e hover passam em 4,5:1. Texto sobre o botão verde, tags verdes e feedback de erro também passa. As barras do gráfico usam um token complementar `--chart-bar: #2f8c59` no escuro para alcançar pelo menos 3:1 contra o card; no claro conservam a cor anterior. Não houve necessidade de ajustar os tokens fornecidos.

As cores claras originais, inclusive o branco já utilizado, foram preservadas. Os componentes do tema escuro não usam branco ou preto puros. As cores de marca do ícone Google e as máscaras técnicas de SVG não foram recoloridas.

## Decisões de identidade

- A alternância de tema mantém a mesma imagem original da marca, incluindo seu símbolo e sua tipografia. O SVG provisório não é utilizado.
- As fotos de usuários e ilustrações existentes mantêm suas cores. Variantes próprias de ilustração para o escuro podem ser adicionadas depois, se desejado.

## Como testar

1. Abra `http://localhost:9999/eco-drop/` ou entre no app. Use o botão de lua/sol no cabeçalho; no app ele fica junto ao perfil. Em autenticação fica no canto superior direito.
2. Alterne os temas, recarregue e navegue por Início, Saques, Extrato, Conta, Estação demo e admin. A escolha deve continuar ativa.
3. Abra uma segunda aba na mesma origem e alterne na primeira: a segunda deve acompanhar a escolha.
4. Para testar a primeira visita, remova somente a chave `ecodrop-theme` no armazenamento local do navegador. Configure o sistema para claro/escuro e recarregue. Uma escolha explícita deve prevalecer sobre mudanças posteriores do sistema.
5. Navegue com Tab e acione o botão com Enter ou Espaço. Confira o foco e os rótulos “Ativar tema claro”/“Ativar tema escuro”.
6. Ative redução de movimento no sistema e confira que as transições de cor do tema não animam. As animações de autenticação já existentes têm uma regra própria que esta implementação não altera.
7. Confira as telas em larguras de celular, tablet e desktop, incluindo tabelas com rolagem, formulários, upload, dropdowns, avisos e o modal de detalhes em Usuários.

Para verificar o tema manualmente: alterne entre claro e escuro em cada página e recarregue para confirmar que a preferência foi persistida.

## Verificação realizada

- Oito testes automatizados passaram.
- Os 12 CSS existentes foram comparados com uma cópia anterior à implementação: medidas, grades, breakpoints e valores do tema claro foram preservados.
- Dezoito páginas foram consultadas pelo Apache local após a sincronização: HTTP 200, script antes dos estilos, botão, logo e recursos carregados corretamente.
- A sintaxe dos templates PHP foi validada.
- A conferência visual em navegador ficou pendente porque a sessão de ferramentas não disponibilizou um navegador. Os testes de contraste são numéricos e não substituem uma auditoria completa de acessibilidade.
