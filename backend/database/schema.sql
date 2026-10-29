CREATE DATABASE IF NOT EXISTS ecodrop
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ecodrop;

SET FOREIGN_KEY_CHECKS = 0;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150)        NOT NULL,
    email           VARCHAR(190)        NOT NULL,
    cpf             CHAR(11)            NOT NULL,
    senha_hash      VARCHAR(255)        NOT NULL,
    google_sub      VARCHAR(255)        NULL,
    foto_google     VARCHAR(2048)       NULL,
    foto_personalizada MEDIUMTEXT       NULL,
    papel           ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario',
    status          ENUM('ativo', 'inativo', 'bloqueado') NOT NULL DEFAULT 'ativo',
    bonus_primeiro_descarte_aplicado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_cpf (cpf),
    UNIQUE KEY uq_usuarios_google_sub (google_sub)
) ENGINE = InnoDB;

-- Recuperação de acesso: o token é armazenado somente como hash;
-- expiração e utilização são verificadas pelo servidor antes de redefinir a senha.
CREATE TABLE IF NOT EXISTS recuperacoes_acesso (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED        NOT NULL,
    token_hash      VARCHAR(255)        NOT NULL,
    expira_em       DATETIME            NOT NULL,
    utilizado_em    DATETIME            NULL,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    UNIQUE KEY uq_recuperacao_token_hash (token_hash),
    KEY idx_recuperacao_usuario (usuario_id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS estacoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(20)         NOT NULL COMMENT 'Código público da estação exibido no app.',
    nome            VARCHAR(150)        NOT NULL,
    endereco        VARCHAR(255)        NULL,
    -- Coordenadas para integração com o mapa (Google Maps).
    latitude        DECIMAL(10, 7)      NULL,
    longitude       DECIMAL(10, 7)      NULL,
    status          ENUM('ativa', 'inativa', 'manutencao') NOT NULL DEFAULT 'ativa',
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estacoes_codigo (codigo),
    UNIQUE KEY uq_estacoes_nome (nome)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS sessoes_estacao (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    estacao_id      INT UNSIGNED        NOT NULL,
    usuario_id      INT UNSIGNED        NULL COMMENT 'Usuário associado ao descarte vinculado à sessão.',
    -- Hash interno preservado para compatibilidade do vínculo histórico.
    token_hash      CHAR(64)            NOT NULL,
    status          ENUM('aguardando_usuario', 'em_uso', 'concluida', 'expirada', 'cancelada') NOT NULL DEFAULT 'aguardando_usuario',
    expira_em       DATETIME            NOT NULL,
    associado_em    DATETIME            NULL,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessao_estacao FOREIGN KEY (estacao_id) REFERENCES estacoes (id) ON DELETE CASCADE,
    CONSTRAINT fk_sessao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    UNIQUE KEY uq_sessao_token_hash (token_hash),
    KEY idx_sessao_estacao (estacao_id),
    KEY idx_sessao_status (status),
    KEY idx_sessao_expira (expira_em)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS materiais (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome                VARCHAR(80)     NOT NULL,
    slug                VARCHAR(80)     NOT NULL,
    unidade             ENUM('kg', 'l') NOT NULL,
    quantidade_minima   DECIMAL(10, 3)  NOT NULL,
    pontos_por_unidade  DECIMAL(10, 2)  NULL,
    status              ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_materiais_slug (slug)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS descartes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_estacao_id   INT UNSIGNED    NOT NULL,
    usuario_id          INT UNSIGNED    NOT NULL,
    estacao_id          INT UNSIGNED    NOT NULL,
    material_id         INT UNSIGNED    NOT NULL,
    quantidade          DECIMAL(10, 3)  NOT NULL,
    quantidade_valida   TINYINT(1)      NOT NULL,
    pontos_gerados      INT UNSIGNED    NOT NULL DEFAULT 0,
    status              ENUM('valido', 'invalido') NOT NULL,
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_descarte_sessao FOREIGN KEY (sessao_estacao_id) REFERENCES sessoes_estacao (id) ON DELETE RESTRICT,
    CONSTRAINT fk_descarte_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT fk_descarte_estacao FOREIGN KEY (estacao_id) REFERENCES estacoes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_descarte_material FOREIGN KEY (material_id) REFERENCES materiais (id) ON DELETE RESTRICT,
    KEY idx_descarte_usuario (usuario_id),
    KEY idx_descarte_criado (criado_em)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS movimentacoes_pontos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED    NOT NULL,
    tipo            ENUM('credito', 'debito') NOT NULL,
    origem          ENUM('descarte', 'bonus_primeiro_descarte', 'resgate', 'estorno') NOT NULL,
    origem_tipo     VARCHAR(30)     NULL,
    origem_id       INT UNSIGNED    NULL,
    quantidade      INT UNSIGNED    NOT NULL,
    descricao       VARCHAR(255)    NOT NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimentacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    KEY idx_movimentacao_usuario (usuario_id),
    KEY idx_movimentacao_origem (origem, origem_id),
    KEY idx_movimentacao_criado (criado_em),
    KEY idx_movimentacao_ranking (tipo, origem, criado_em)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave           VARCHAR(80)     NOT NULL PRIMARY KEY,
    valor           VARCHAR(255)    NULL,
    descricao       VARCHAR(255)    NOT NULL,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS parceiros (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150)    NOT NULL,
    descricao       VARCHAR(255)    NULL,
    contato         VARCHAR(150)    NULL,
    status          ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_parceiros_nome (nome)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS recompensas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parceiro_id     INT UNSIGNED    NULL,
    nome            VARCHAR(150)    NOT NULL,
    descricao       TEXT            NULL,
    custo_pontos    INT UNSIGNED    NULL,
    estoque         INT             NULL COMMENT 'NULL = estoque ilimitado/não controlado.',
    validade_dias   INT UNSIGNED    NULL,
    imagem_url      VARCHAR(255)    NULL,
    status          ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_recompensa_parceiro FOREIGN KEY (parceiro_id) REFERENCES parceiros (id) ON DELETE SET NULL,
    UNIQUE KEY uq_recompensas_nome (nome)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS resgates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED    NOT NULL,
    recompensa_id   INT UNSIGNED    NOT NULL,
    pontos_debitados INT UNSIGNED   NOT NULL,
    status          ENUM('confirmado', 'estornado') NOT NULL DEFAULT 'confirmado',
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resgate_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT fk_resgate_recompensa FOREIGN KEY (recompensa_id) REFERENCES recompensas (id) ON DELETE RESTRICT,
    KEY idx_resgate_usuario (usuario_id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS vouchers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resgate_id      INT UNSIGNED    NOT NULL,
    usuario_id      INT UNSIGNED    NOT NULL,
    codigo          VARCHAR(30)     NOT NULL,
    qr_code_token   CHAR(64)        NOT NULL,
    validade_em     DATETIME        NULL,
    status          ENUM('disponivel', 'utilizado', 'expirado', 'cancelado') NOT NULL DEFAULT 'disponivel',
    utilizado_em    DATETIME        NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_voucher_resgate FOREIGN KEY (resgate_id) REFERENCES resgates (id) ON DELETE RESTRICT,
    CONSTRAINT fk_voucher_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    UNIQUE KEY uq_voucher_codigo (codigo),
    UNIQUE KEY uq_voucher_qr (qr_code_token),
    KEY idx_voucher_usuario (usuario_id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS premiacoes_ranking (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    posicao         TINYINT UNSIGNED NOT NULL,
    descricao       VARCHAR(255)    NOT NULL,
    recompensa_id   INT UNSIGNED    NULL,
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_premiacao_recompensa FOREIGN KEY (recompensa_id) REFERENCES recompensas (id) ON DELETE SET NULL,
    UNIQUE KEY uq_premiacao_posicao (posicao)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS auditoria_administrativa (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT UNSIGNED    NOT NULL,
    acao            VARCHAR(80)     NOT NULL,
    entidade        VARCHAR(80)     NOT NULL,
    entidade_id     INT UNSIGNED    NULL,
    justificativa   TEXT            NULL,
    detalhes_json   JSON            NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_admin FOREIGN KEY (admin_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    KEY idx_auditoria_entidade (entidade, entidade_id)
) ENGINE = InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

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

CREATE TABLE IF NOT EXISTS propostas_parceria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizacao VARCHAR(150) NOT NULL,
    responsavel VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(40) NOT NULL,
    cidade VARCHAR(150) NOT NULL,
    segmento VARCHAR(100) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    proposta TEXT NOT NULL,
    status ENUM('nova','em_analise','aprovada','recusada') NOT NULL DEFAULT 'nova',
    notas TEXT NULL,
    avaliado_por INT UNSIGNED NULL,
    avaliado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_proposta_avaliador FOREIGN KEY (avaliado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    KEY idx_propostas_status_data (status, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tentativas de acesso para limitar força bruta e abuso (rate limiting).
-- O identificador é um hash do par (e-mail/IP): não guarda dado pessoal em claro.
CREATE TABLE IF NOT EXISTS tentativas_acesso (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escopo VARCHAR(40) NOT NULL COMMENT 'Ex.: login, recuperacao.',
    identificador CHAR(64) NOT NULL COMMENT 'SHA-256 do identificador (e-mail/IP).',
    ip VARCHAR(45) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tentativas_busca (escopo, identificador, criado_em),
    KEY idx_tentativas_limpeza (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
