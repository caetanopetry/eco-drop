-- Passa a guardar somente o hash SHA-256 do token das sessões de estação.
-- Aplique uma única vez em bancos criados antes de 18/10/2026. Em instalações
-- novas, a estrutura já está em schema.sql. Sessões pendentes no momento da
-- migração são preservadas: o hash é calculado a partir do token existente.
ALTER TABLE sessoes_estacao
    ADD COLUMN token_hash CHAR(64) NULL AFTER usuario_id;

UPDATE sessoes_estacao
    SET token_hash = SHA2(token, 256)
    WHERE token_hash IS NULL;

ALTER TABLE sessoes_estacao
    MODIFY COLUMN token_hash CHAR(64) NOT NULL;

ALTER TABLE sessoes_estacao
    DROP INDEX uq_sessao_token;

ALTER TABLE sessoes_estacao
    ADD UNIQUE KEY uq_sessao_token_hash (token_hash);

ALTER TABLE sessoes_estacao
    DROP COLUMN token;
