-- EcoDrop - atualização de banco para instalações já existentes
--
-- Aplicar uma única vez em bancos criados antes de 2026-10-23.
-- Instalações novas já recebem todas as estruturas pelo schema.sql.
--
-- O script é idempotente: pode ser executado novamente sem erro.

USE ecodrop;
SET NAMES utf8mb4;

-- 1) Tabela usada pelo RateLimiter (login, recuperação de senha etc.).
--    Sem ela, qualquer tentativa de login termina em erro fatal.
CREATE TABLE IF NOT EXISTS tentativas_acesso (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escopo          VARCHAR(40) NOT NULL COMMENT 'Ex.: login, recuperacao.',
    identificador   CHAR(64)    NOT NULL COMMENT 'SHA-256 do identificador (e-mail/IP).',
    ip              VARCHAR(45) NULL,
    criado_em       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tentativas_busca (escopo, identificador, criado_em),
    KEY idx_tentativas_limpeza (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Alinha a coluna da sessão de estação com o código (token_hash).
--    O nome antigo era "token" e não é mais utilizado pelo repositório.
SET @tem_coluna_token := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sessoes_estacao'
      AND COLUMN_NAME = 'token'
);

SET @sql_atualizacao := IF(
    @tem_coluna_token > 0,
    'ALTER TABLE sessoes_estacao CHANGE token token_hash CHAR(64) NOT NULL',
    'SELECT ''sessoes_estacao.token_hash já está atualizado'' AS aviso'
);

PREPARE stmt_atualizacao FROM @sql_atualizacao;
EXECUTE stmt_atualizacao;
DEALLOCATE PREPARE stmt_atualizacao;

-- 3) Materiais adicionais para a separação por categoria no painel admin.
--    A inserção só ocorre quando o slug ainda não existe.
INSERT INTO materiais (nome, slug, unidade, quantidade_minima, pontos_por_unidade, status)
SELECT 'Metal', 'metal', 'kg', 0.100, 200, 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM materiais WHERE slug = 'metal');

INSERT INTO materiais (nome, slug, unidade, quantidade_minima, pontos_por_unidade, status)
SELECT 'Organico', 'organico', 'kg', 0.100, 60, 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM materiais WHERE slug = 'organico');

-- 4) Código público das lixeiras (estações), exibido no app após a seleção.
--    As lixeiras já cadastradas recebem um código sequencial único.
SET @tem_coluna_codigo := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'estacoes'
      AND COLUMN_NAME = 'codigo'
);

SET @sql_atualizacao := IF(
    @tem_coluna_codigo = 0,
    'ALTER TABLE estacoes ADD COLUMN codigo VARCHAR(20) NULL AFTER id',
    'SELECT ''estacoes.codigo já existe'' AS aviso'
);

PREPARE stmt_atualizacao FROM @sql_atualizacao;
EXECUTE stmt_atualizacao;
DEALLOCATE PREPARE stmt_atualizacao;

UPDATE estacoes
SET codigo = CONCAT('ECO-', LPAD(id, 3, '0'))
WHERE codigo IS NULL OR codigo = '';

SET @tem_indice_codigo := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'estacoes'
      AND INDEX_NAME = 'uq_estacoes_codigo'
);

SET @sql_atualizacao := IF(
    @tem_indice_codigo = 0,
    'ALTER TABLE estacoes ADD UNIQUE KEY uq_estacoes_codigo (codigo)',
    'SELECT ''Índice uq_estacoes_codigo já existe'' AS aviso'
);

PREPARE stmt_atualizacao FROM @sql_atualizacao;
EXECUTE stmt_atualizacao;
DEALLOCATE PREPARE stmt_atualizacao;

ALTER TABLE estacoes MODIFY codigo VARCHAR(20) NOT NULL;
