-- =====================================================================
-- EcoDrop - Dados iniciais para desenvolvimento local
-- =====================================================================

USE ecodrop;
SET NAMES utf8mb4;

-- Materiais aceitos no MVP. Os pontos sao ficticios para a demo local.
INSERT INTO materiais (nome, slug, unidade, quantidade_minima, pontos_por_unidade, status)
VALUES
    ('Plastico', 'plastico', 'kg', 0.100, 150, 'ativo'),
    ('Papel', 'papel', 'kg', 0.100, 90, 'ativo'),
    ('Vidro', 'vidro', 'kg', 0.100, 120, 'ativo'),
    ('Metal', 'metal', 'kg', 0.100, 200, 'ativo'),
    ('Organico', 'organico', 'kg', 0.100, 60, 'ativo'),
    ('Oleo de cozinha usado', 'oleo-cozinha-usado', 'l', 0.500, 220, 'ativo')
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    unidade = VALUES(unidade),
    quantidade_minima = VALUES(quantidade_minima),
    pontos_por_unidade = COALESCE(materiais.pontos_por_unidade, VALUES(pontos_por_unidade)),
    status = VALUES(status);

INSERT INTO configuracoes (chave, valor, descricao)
VALUES
    ('bonus_primeiro_descarte_pontos', '100', 'Bonus ficticio concedido uma unica vez no primeiro descarte valido.'),
    ('sessao_estacao_uso_segundos', '900', 'Tempo maximo, em segundos, para concluir uma pesagem.')
ON DUPLICATE KEY UPDATE
    valor = COALESCE(configuracoes.valor, VALUES(valor)),
    descricao = VALUES(descricao);

INSERT INTO estacoes (codigo, nome, endereco, latitude, longitude, status)
SELECT CONCAT('ECO-', LPAD(COALESCE(MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)), 0) + 1, 3, '0')),
       'Estacao EcoDrop Demo', 'Endereco de demonstracao para testes locais', -23.5505200, -46.6333080, 'ativa'
FROM estacoes
HAVING NOT EXISTS (SELECT 1 FROM estacoes WHERE nome = 'Estacao EcoDrop Demo');

INSERT INTO estacoes (codigo, nome, endereco, latitude, longitude, status)
SELECT CONCAT('ECO-', LPAD(COALESCE(MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)), 0) + 1, 3, '0')),
       'EcoDrop Centro', 'Praca Central, 100', -23.5505200, -46.6333080, 'ativa'
FROM estacoes
HAVING NOT EXISTS (SELECT 1 FROM estacoes WHERE nome = 'EcoDrop Centro');

INSERT INTO estacoes (codigo, nome, endereco, latitude, longitude, status)
SELECT CONCAT('ECO-', LPAD(COALESCE(MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)), 0) + 1, 3, '0')),
       'EcoDrop Parque Verde', 'Rua das Arvores, 42', -23.5558000, -46.6401000, 'ativa'
FROM estacoes
HAVING NOT EXISTS (SELECT 1 FROM estacoes WHERE nome = 'EcoDrop Parque Verde');

UPDATE estacoes
SET status = 'ativa'
WHERE nome IN ('Estacao EcoDrop Demo', 'EcoDrop Centro', 'EcoDrop Parque Verde');

UPDATE estacoes
SET codigo = CASE nome
        WHEN 'Estacao EcoDrop Demo' THEN 'ECO-001'
        WHEN 'EcoDrop Centro' THEN 'ECO-002'
        WHEN 'EcoDrop Parque Verde' THEN 'ECO-003'
    END
WHERE nome IN ('Estacao EcoDrop Demo', 'EcoDrop Centro', 'EcoDrop Parque Verde')
  AND (codigo IS NULL OR codigo = '');

INSERT INTO parceiros (nome, descricao, contato, status)
SELECT 'Mercado Verde', 'Parceiro ficticio para recompensas do MVP.', 'contato@mercadoverde.local', 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM parceiros WHERE nome = 'Mercado Verde');

INSERT INTO parceiros (nome, descricao, contato, status)
SELECT 'Cafe Circular', 'Parceiro ficticio para vouchers sustentaveis.', 'contato@cafecircular.local', 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM parceiros WHERE nome = 'Cafe Circular');

UPDATE parceiros
SET status = 'ativo'
WHERE nome IN ('Mercado Verde', 'Cafe Circular');

INSERT INTO recompensas (parceiro_id, nome, descricao, custo_pontos, estoque, validade_dias, status)
SELECT p.id, 'Cupom Mercado Verde', 'Desconto ficticio de R$ 10 em compras selecionadas.', 450, 25, 30, 'ativo'
FROM parceiros p
WHERE p.nome = 'Mercado Verde'
    AND NOT EXISTS (SELECT 1 FROM recompensas WHERE nome = 'Cupom Mercado Verde');

INSERT INTO recompensas (parceiro_id, nome, descricao, custo_pontos, estoque, validade_dias, status)
SELECT p.id, 'Cafe sustentavel', 'Voucher ficticio para uma bebida pequena.', 300, 40, 15, 'ativo'
FROM parceiros p
WHERE p.nome = 'Cafe Circular'
    AND NOT EXISTS (SELECT 1 FROM recompensas WHERE nome = 'Cafe sustentavel');

UPDATE recompensas
SET status = 'ativo'
WHERE nome IN ('Cupom Mercado Verde', 'Cafe sustentavel');

-- Senhas em texto puro apenas para ambiente LOCAL:
-- Administrador: admin@ecodrop.local / EcoDrop@123
-- Usuario comum: usuario@ecodrop.local / Usuario@123
INSERT INTO usuarios (nome, email, cpf, senha_hash, papel, status)
VALUES
    ('Administrador EcoDrop', 'admin@ecodrop.local', '00000000000',
     '$2b$10$0V93FoLI7st9VPuj6EChvuk8Rk6cph4TgWFDRTJ2xBoZNeZDhroJG', 'admin', 'ativo'),
    ('Usuario de Teste', 'usuario@ecodrop.local', '11111111111',
     '$2b$10$e0b51RoeEChn/weq311nLelQXPP6k60E0cG0/T0SFTbQlBzk/2BH2', 'usuario', 'ativo')
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    papel = VALUES(papel),
    status = VALUES(status);
