-- =====================================================================
-- EcoDrop — Dados iniciais (seed)
-- =====================================================================
-- Contém apenas o que já está definido na documentação (docs/visao-geral.md,
-- docs/escopo.md). Valores comerciais/pontuação NÃO são inventados: ficam
-- NULL, prontos para configuração futura pelo Admin (RN008, RN017, RN039).
-- =====================================================================

USE ecodrop;

-- Garante que o cliente que está executando este script (linha de comando, phpMyAdmin, etc.)
-- envie os dados como utf8mb4, independentemente do charset padrão da ferramenta usada.
-- Sem isso, nomes com acento podem ser gravados com "double encoding".
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Materiais iniciais (docs/visao-geral.md)
-- ---------------------------------------------------------------------
INSERT INTO materiais (nome, slug, unidade, quantidade_minima, pontos_por_unidade, status)
VALUES
    ('Plástico', 'plastico', 'kg', 0.100, NULL, 'ativo'),
    ('Papel', 'papel', 'kg', 0.100, NULL, 'ativo'),
    ('Vidro', 'vidro', 'kg', 0.100, NULL, 'ativo'),
    ('Óleo de cozinha usado', 'oleo-cozinha-usado', 'l', 0.500, NULL, 'ativo')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- ---------------------------------------------------------------------
-- Configurações pendentes de definição de negócio (docs/escopo.md, RN017)
-- ---------------------------------------------------------------------
INSERT INTO configuracoes (chave, valor, descricao)
VALUES
    ('bonus_primeiro_descarte_pontos', NULL, 'Valor do bônus concedido no primeiro descarte válido do usuário (RN016/RN017). PENDENTE.'),
    ('qr_code_validade_segundos', '120', 'Validade do QR Code/sessão temporária da estação, em segundos (RN018).')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

-- ---------------------------------------------------------------------
-- Estação de demonstração, para permitir testes locais (Fase 2 em diante)
-- ---------------------------------------------------------------------
INSERT INTO estacoes (nome, endereco, status)
VALUES ('Estação EcoDrop — Demonstração', 'Endereço de demonstração para testes locais', 'ativa')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- ---------------------------------------------------------------------
-- Usuários de teste
-- ---------------------------------------------------------------------
-- Senha em texto puro (apenas para uso em ambiente LOCAL de desenvolvimento):
--   Administrador -> admin@ecodrop.local     / EcoDrop@123
--   Usuário comum -> usuario@ecodrop.local   / Usuario@123
-- Os hashes abaixo foram gerados com o mesmo algoritmo bcrypt usado por
-- password_hash()/password_verify() do PHP.
INSERT INTO usuarios (nome, email, cpf, senha_hash, papel, status)
VALUES
    ('Administrador EcoDrop', 'admin@ecodrop.local', '00000000000',
     '$2b$10$0V93FoLI7st9VPuj6EChvuk8Rk6cph4TgWFDRTJ2xBoZNeZDhroJG', 'admin', 'ativo'),
    ('Usuário de Teste', 'usuario@ecodrop.local', '11111111111',
     '$2b$10$e0b51RoeEChn/weq311nLelQXPP6k60E0cG0/T0SFTbQlBzk/2BH2', 'usuario', 'ativo')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
