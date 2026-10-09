-- Sessões autenticadas. O identificador PHP é armazenado somente como hash.
CREATE TABLE IF NOT EXISTS sessoes_acesso (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    iniciado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_atividade_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    encerrado_em DATETIME NULL,
    UNIQUE KEY uq_acesso_token (token_hash),
    KEY idx_acesso_usuario (usuario_id, iniciado_em),
    KEY idx_acesso_online (encerrado_em, ultima_atividade_em),
    CONSTRAINT fk_acesso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
