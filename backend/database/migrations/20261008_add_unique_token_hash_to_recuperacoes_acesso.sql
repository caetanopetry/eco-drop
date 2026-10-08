-- Garante que cada hash de token de recuperação seja único no banco.
ALTER TABLE recuperacoes_acesso
    ADD UNIQUE KEY uq_recuperacao_token_hash (token_hash);
