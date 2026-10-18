-- Cria a tabela de controle de tentativas (rate limiting) usada no login e na
-- recuperação de senha. Aplique uma única vez em bancos criados antes de
-- 18/10/2026. Em instalações novas, a tabela já está em schema.sql.
CREATE TABLE IF NOT EXISTS tentativas_acesso (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escopo VARCHAR(40) NOT NULL,
    identificador CHAR(64) NOT NULL,
    ip VARCHAR(45) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tentativas_busca (escopo, identificador, criado_em),
    KEY idx_tentativas_limpeza (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
