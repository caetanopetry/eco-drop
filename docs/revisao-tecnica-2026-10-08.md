# Revisão técnica do EcoDrop — 08/10/2026

## Escopo e resultado

Revisão do código, configuração, banco, rotas, autenticação, regras de pontos,
resgates, interfaces públicas e privadas, admin, tema e dependências. Foram
executados testes de integração e HTTP local, além de validação de sintaxe e
testes JavaScript. Isso não equivale a certificar ausência de erros ou a uma
auditoria de produção. Google, entrega SMTP, hardware e conferência visual no
navegador não foram exercitados nesta revisão.

## Arquitetura e cobertura

| Área | Implementação e avaliação |
| --- | --- |
| Stack | PHP sem framework, HTML/CSS/JavaScript, PDO MySQL/MariaDB, Apache e Composer. Controllers e páginas utilizam services, repositories e core compartilhados. |
| Entrada e configuração | `.htaccess`, `backend/config`, `Config`, `Database`, `Url` e `.env`; arquivos internos agora bloqueados via HTTP. |
| Autenticação | Cadastro, login, sessões, papéis, logout, fluxo Google e recuperação. SQL preparado e hashes de senha; acesso e senha revalidados nas sessões. |
| Perfil | Conta e fotos; validação de tamanho, dimensões e tipo real de imagem revisada no código. Upload bem-sucedido não foi exercitado via HTTP. |
| Estação | Identificação temporária e descarte demonstrativo, validade, mínimo e bônus. Não há integração física ou autorização operacional de produção. |
| Carteira | Créditos, extrato, ranking, recompensas e vouchers; transações e bloqueio da carteira contra gasto simultâneo. |
| Administração | Dashboard e Usuários consultam o banco. Indicadores, filtros, paginação visual e histórico de sessões; sidebar fixa no desktop. Outros cadastros do menu permanecem incompletos. |
| Frontend | Layouts compartilhados, páginas institucionais, app, estação, admin e autenticação. Tokens claro/escuro, persistência e foco; novos formulários de recuperação receberam o tema. |
| Banco | Schema, seed e migrações inspecionados. Banco temporário utilizado para testes; migração aditiva do índice de recuperação aplicada ao banco local após checagem. |
| Dependências | PHPMailer 7.1.1 instalado junto às dependências existentes. Composer validou requisitos da plataforma; auditoria não encontrou avisos de vulnerabilidade nas versões instaladas. |

## Problemas corrigidos

| Problema observado | Correção e arquivos |
| --- | --- |
| Crítico: `.env`, metadados Git e SQL respondiam HTTP 200 no Apache local | `.htaccess` bloqueia arquivos sensíveis, dependências, testes e camadas internas. Verificado HTTP 403; páginas, APIs públicas e assets continuam acessíveis. Não há evidência nesta revisão sobre acesso anterior por terceiros. |
| Conta bloqueada ou senha alterada mantinha sessão aberta | `backend/core/Auth.php` e `UsuarioRepository.php` revalidam status, papel e impressão do hash de senha. |
| Falha de telemetria podia impedir login/logout se a migração estivesse ausente | `Auth.php` registra a falha sem derrubar autenticação; teste negativo intencional cobre essa condição. |
| Recuperação preservava espaços na senha, mas login os removia | `Request.php` permite leitura sem aparar; endpoints e `AuthService.php` preservam senhas e limitam nome ao tamanho da coluna. |
| Resgates de recompensas diferentes podiam disputar o mesmo saldo | `EcoDropRepository.php` bloqueia primeiro a conta dentro da transação. Teste com dois processos comprova apenas um resgate quando o saldo cobre um deles. |
| Posição pessoal desaparecia depois do décimo colocado | Consulta pessoal usa ranking completo, mantendo os dez primeiros no painel. |
| Quantidades inválidas ou além do tipo SQL geravam falhas | Descarte valida finitude, intervalo da quantidade e capacidade do campo de pontos antes da gravação. |
| Ordenação de bloqueios da recuperação podia concorrer; falha de envio antigo podia invalidar token mais novo | `UsuarioRepository.php` usa ordem usuário/token; `PasswordRecoveryService.php` invalida somente o token cujo envio falhou. |
| Código HTTP 419 era convertido em 500 pelo ambiente local; CSRF em array causava erro de tipo | APIs de autenticação, Conta, Saques e Estação devolvem 403 para CSRF inválido; formulários verificam o tipo do token. |
| Documentação descrevia apenas a primeira fase | `README.md` atualizado para o estado real, rotas, migrações e testes. |

## Sincronização do repositório

Foi feito `fetch origin --prune`, seguido de avanço direto da branch `main`, de
`9f35b99` para `c33ed63`. Os três commits recebidos foram:

- `1fb2afa`: OAuth Google e recuperação de senha.
- `af3d082`: complementos de OAuth Google e recuperação de senha.
- `c33ed63`: README e revisão ponta a ponta.

As alterações locais foram preservadas antes da integração e reaplicadas sem
conflitos textuais. Schema, CSS de autenticação e layout foram mesclados
automaticamente. A revisão semântica integrou o tema às novas páginas, instalou
PHPMailer e corrigiu os problemas de recuperação descritos acima.

Backup externo ao repositório:
`C:\Users\caioe\AppData\Local\EcoDrop-backups\pre-sync-20261008-212818`.
O stash de segurança também foi mantido. Nenhum commit ou push foi realizado.

## Verificações

- 14 grupos PHP em banco temporário: cadastro, duplicidades, autenticação,
  sessões, CSRF, bloqueio, QR, descarte, bônus, resgate, estoque, saldo, extrato,
  ranking, admin, recuperação, revogação e concorrência. Banco removido ao final.
- 44 verificações HTTP no Apache local, incluindo 18 páginas, recursos do tema,
  perfis, redirecionamentos, endpoints e bloqueio de arquivos internos.
- 11 testes JavaScript: tema, persistência, preferência do sistema, sincronização,
  contraste dos tokens, paginação, filtros e escape dos dados no admin.
- Sintaxe validada em 77 arquivos PHP e 13 arquivos JavaScript/CJS.
- Composer: configuração válida e requisitos atendidos; aviso de licença não
  declarada. Auditoria das dependências instaladas sem avisos de vulnerabilidade.

Os avisos sobre `sessoes_acesso` no teste PHP são esperados: o último cenário
remove essa tabela somente no banco descartável para testar a tolerância à
migração pendente. Não indicam falha no banco local.

## Pendências para operação pública

1. Restringir a estação demo a operadores autorizados: hoje sua interface é
   pública e pode registrar pontos com um token de formulário válido.
2. Implementar limites de tentativas de login/recuperação, política de expiração
   absoluta de sessões e implantação HTTPS com cookies seguros. Logout por GET
   ainda permite encerramento forçado de sessão por navegação externa.
3. Validar autenticação Google e entrega SMTP no ambiente de destino; não foram
   feitos logins no provedor nem envios de e-mail nesta revisão.
4. Separar preparação de dados demo das requisições normais e decidir sobre sua
   disponibilidade em produção.
5. Mover paginação/filtros de Usuários para SQL quando o volume crescer; hoje os
   registros são carregados para filtragem no navegador.
6. Completar administração de estações, materiais, parceiros e recompensas,
   premiação automática, mapas e integrações físicas conforme escopo do produto.
7. Aprovar o logo SVG provisório, conferir layouts móveis e acessibilidade
   visual. A ferramenta não disponibilizou navegador para inspeção visual.
8. Definir licença e política de versionamento de `composer.lock`, atualmente
   ignorado pelo padrão existente; versões futuras podem variar na instalação.
9. Completar identificação do responsável, contato e retenção dos dados antes
   da publicação das informações de privacidade.

## Preparação do commit manual

Arquivos de implementação, documentação e testes desta entrega são preparados
no staging. `.env`, `vendor/` e `composer.lock` continuam ignorados.
`backend/database/schema copy.sql` foi preservado e excluído da preparação:
é uma cópia local não necessária à implementação.

Revise com `git diff --cached` e faça seu commit manual. Em outro ambiente, use
`composer install` e aplique as migrações pendentes antes de validar o admin e
a recuperação. Não recrie um banco existente pelo schema.

Comandos de verificação e detalhes do tema estão no README e em
`docs/tema-escuro.md`. O staging não inclui a instalação local de dependências
nem a aplicação local de migrações; esses passos devem ser repetidos no destino.
