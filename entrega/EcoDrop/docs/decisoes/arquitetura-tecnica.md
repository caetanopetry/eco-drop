# Decisão — Arquitetura técnica de implementação

> `docs/arquitetura/README.md` definiu diretrizes e deixou explicitamente em aberto
> "estratégia técnica de autenticação", "banco de dados específico" e a estrutura de
> aplicação/API. Este documento registra as decisões tomadas na implementação,
> conforme pedido na seção de diretrizes daquele arquivo.

## Roteamento sem framework

Cada rota da seção 28 do escopo (`/app/login`, `/admin/dashboard`, etc.) é uma pasta
real com um `index.php` fino, que apenas inclui a página correspondente em
`frontend/pages/{area}/{pagina}.php`:

```
app/login/index.php  →  require frontend/pages/auth/login.php
```

O Apache do XAMPP já serve `index.php` para uma requisição de diretório sem
nenhuma configuração adicional (`DirectoryIndex` padrão), então URLs limpas
funcionam sem `.htaccess`/`mod_rewrite`. Isso evita a complexidade de um roteador
central nesta fase, mantendo a estrutura de pastas pedida em `frontend/pages/`
como a organização "de verdade" do código (roteamento e apresentação separados).

A API segue o mesmo princípio: cada endpoint é um arquivo físico dentro de
`backend/api/{modulo}/{acao}.php` (ex.: `backend/api/auth/login.php`), chamado
diretamente pelo frontend via `fetch()`. Não há um roteador de API dedicado nesta
fase; se a quantidade de endpoints crescer muito, um front controller pode ser
introduzido depois sem quebrar os contratos JSON já definidos.

## Autenticação

- Sessão nativa do PHP (`session_start`), cookie `HttpOnly` + `SameSite=Lax`.
- `password_hash()` / `password_verify()` (bcrypt), nunca texto puro (RNF002).
- `session_regenerate_id(true)` no login, para mitigar fixação de sessão.
- Papel (`usuario`/`admin`) é lido exclusivamente da sessão do servidor — nunca de
  um campo enviado pelo cliente (RNF004, RNF015).
- Proteção CSRF simples: token por sessão, exigido em todo POST que altera estado
  (`Csrf::validar`).

## Camadas do backend

```
backend/
├── api/           # Endpoints HTTP (entrada/saída JSON, validação de método/CSRF)
├── config/        # Configuração central + carregamento
├── core/          # Infraestrutura: PDO, sessão, request, resposta, validação
├── services/      # Regras de negócio (ex.: AuthService)
├── repositories/  # Acesso a dados via PDO/prepared statements
└── database/      # schema.sql, seed.sql
```

Um endpoint de API nunca executa SQL diretamente: ele chama um `Service`, que usa
um `Repository`. Isso mantém testável e trocável a camada de dados sem tocar nas
regras de negócio.

## CPF: validação técnica vs. regra de negócio

`docs/casos-de-uso/README.md` (UC001) marca como PENDENTE "a forma de validação dos
campos além da unicidade". Optamos por validar o dígito verificador do CPF
(`Validator::cpf`) por ser um algoritmo matemático padrão e público — não uma regra
de negócio do EcoDrop — evitando cadastro de CPFs estruturalmente inválidos.
Validações adicionais (ex.: consulta a serviço externo de CPF, bloqueio de CPFs
já usados em fraude) continuam pendentes e fora desta fase.

## Testabilidade local

Os usuários de teste em `backend/database/seed.sql` usam CPFs sequenciais
(`00000000000`, `11111111111`) apenas para facilitar testes manuais. Esses CPFs
não passariam na validação de cadastro da aplicação (`Validator::cpf` rejeita
sequências repetidas) — são inseridos diretamente via SQL, fora do fluxo de
cadastro, e servem só para login local.
