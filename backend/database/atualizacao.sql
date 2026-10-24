-- EcoDrop - atualização de banco para instalações já existentes
--
-- Aplicar uma única vez em bancos criados antes de 2026-10-23.
-- Instalações novas já recebem todas as estruturas pelo schema.sql.
--
-- O script é idempotente: pode ser executado novamente sem erro.

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
