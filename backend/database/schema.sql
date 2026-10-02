-- =====================================================================
-- EcoDrop — Schema do banco de dados
-- =====================================================================
-- Modelado a partir de docs/requisitos/*, docs/casos-de-uso/README.md e
-- docs/fluxos/README.md, já que docs/banco-de-dados/README.md não existe
-- no repositório. Decisões e justificativas em docs/decisoes/banco-de-dados.md.
--
-- Convenções:
--   - InnoDB (transações e chaves estrangeiras).
--   - utf8mb4 (acentuação/emoji).
--   - Todo valor comercial/pontuação é configurável em tabela (RN008, RN039),
--     nunca fixo em código, e pode ficar NULL/pendente até definição.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS ecodrop
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ecodrop;

SET FOREIGN_KEY_CHECKS = 0;

-- Garante que este script seja interpretado como utf8mb4 pelo cliente que o executa
-- (linha de comando, phpMyAdmin, etc.), evitando problemas de acentuação.
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Conta e acesso (RF001-RF005, RN014, RN015, RNF002, RNF016)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150)        NOT NULL,
    email           VARCHAR(190)        NOT NULL,
    cpf             CHAR(11)            NOT NULL,
    senha_hash      VARCHAR(255)        NOT NULL,
    google_sub      VARCHAR(255)        NULL,
    -- 'usuario' (AT01) ou 'admin' (AT02). Sem tabela separada de administradores:
    -- decisão registrada em docs/decisoes/arquitetura-tecnica.md.
    papel           ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario',
    status          ENUM('ativo', 'inativo', 'bloqueado') NOT NULL DEFAULT 'ativo',
    -- RF028/RN016: controla se o bônus de primeiro descarte já foi concedido a este usuário/CPF.
    bonus_primeiro_descarte_aplicado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_cpf (cpf),
    UNIQUE KEY uq_usuarios_google_sub (google_sub)
) ENGINE = InnoDB;

-- RF005 - Recuperação de acesso. Fluxo/canal detalhado é PENDENTE (docs/casos-de-uso UC003);
-- estrutura mínima de token deixada pronta para quando o mecanismo for definido.
CREATE TABLE IF NOT EXISTS recuperacoes_acesso (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED        NOT NULL,
    token_hash      VARCHAR(255)        NOT NULL,
    expira_em       DATETIME            NOT NULL,
    utilizado_em    DATETIME            NULL,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    KEY idx_recuperacao_usuario (usuario_id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Estações (RF006-RF009, RF045)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS estacoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150)        NOT NULL,
    endereco        VARCHAR(255)        NULL,
    -- Coordenadas para o mapa (RF008). Integração definitiva com Google Maps é PENDENTE.
    latitude        DECIMAL(10, 7)      NULL,
    longitude       DECIMAL(10, 7)      NULL,
    status          ENUM('ativa', 'inativa', 'manutencao') NOT NULL DEFAULT 'ativa',
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estacoes_nome (nome)
) ENGINE = InnoDB;

-- Sessões temporárias da estação + QR Code (RF010-RF014, RN018-RN020, RNF008)
CREATE TABLE IF NOT EXISTS sessoes_estacao (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    estacao_id      INT UNSIGNED        NOT NULL,
    usuario_id      INT UNSIGNED        NULL COMMENT 'Preenchido quando o usuário se associa via QR Code ou credenciais.',
    -- RN019: o código do QR nunca deve conter dado sensível — é apenas um token opaco.
    token           CHAR(64)            NOT NULL,
    status          ENUM('aguardando_usuario', 'em_uso', 'concluida', 'expirada', 'cancelada') NOT NULL DEFAULT 'aguardando_usuario',
    expira_em       DATETIME            NOT NULL COMMENT 'RN018: validade de 2 minutos a partir da criação.',
    associado_em    DATETIME            NULL,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessao_estacao FOREIGN KEY (estacao_id) REFERENCES estacoes (id) ON DELETE CASCADE,
    CONSTRAINT fk_sessao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    UNIQUE KEY uq_sessao_token (token),
    KEY idx_sessao_estacao (estacao_id),
    KEY idx_sessao_status (status),
    KEY idx_sessao_expira (expira_em)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Materiais e pontuação (RN001-RN010, RF026, RF027)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS materiais (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome                VARCHAR(80)     NOT NULL,
    slug                VARCHAR(80)     NOT NULL,
    unidade             ENUM('kg', 'l') NOT NULL COMMENT 'RN002/RN003: sólidos em kg, óleo em litros.',
    quantidade_minima   DECIMAL(10, 3)  NOT NULL COMMENT 'RN004/RN005: mínimo válido para pontuação, na unidade do material.',
    -- RN007/RN008: pontos por unidade, configurável pelo admin. NULL = ainda não definido (pendência de negócio).
    pontos_por_unidade  DECIMAL(10, 2)  NULL,
    status              ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_materiais_slug (slug)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Descartes (RF015-RF023, RN001-RN013, RN021)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS descartes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_estacao_id   INT UNSIGNED    NOT NULL,
    usuario_id          INT UNSIGNED    NOT NULL,
    estacao_id          INT UNSIGNED    NOT NULL,
    material_id         INT UNSIGNED    NOT NULL,
    quantidade          DECIMAL(10, 3)  NOT NULL COMMENT 'Quantidade simulada/informada pela estação (RF019).',
    quantidade_valida   TINYINT(1)      NOT NULL COMMENT 'Resultado da validação de mínimo (RF018).',
    pontos_gerados      INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Já arredondado (RN009, RN010). 0 se descarte inválido.',
    status              ENUM('valido', 'invalido') NOT NULL,
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_descarte_sessao FOREIGN KEY (sessao_estacao_id) REFERENCES sessoes_estacao (id) ON DELETE RESTRICT,
    CONSTRAINT fk_descarte_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    CONSTRAINT fk_descarte_estacao FOREIGN KEY (estacao_id) REFERENCES estacoes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_descarte_material FOREIGN KEY (material_id) REFERENCES materiais (id) ON DELETE RESTRICT,
    KEY idx_descarte_usuario (usuario_id),
    KEY idx_descarte_criado (criado_em)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Pontos: extrato/movimentações (RF024, RF025, RN009, RN011-RN013, RN027, RN028)
-- ---------------------------------------------------------------------
-- Decisão: não existe coluna de "saldo" isolada tratada como fonte de verdade.
-- O saldo é a soma das movimentações (RNF015: consistência não pode depender
-- de um único campo mutável). Ver docs/decisoes/banco-de-dados.md.
CREATE TABLE IF NOT EXISTS movimentacoes_pontos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED    NOT NULL,
    tipo            ENUM('credito', 'debito') NOT NULL,
    origem          ENUM('descarte', 'bonus_primeiro_descarte', 'resgate', 'estorno') NOT NULL,
    -- Referência genérica ao registro de origem (descarte, resgate ou estorno), para rastreabilidade (RNF007).
    origem_tipo     VARCHAR(30)     NULL,
    origem_id       INT UNSIGNED    NULL,
    quantidade      INT UNSIGNED    NOT NULL COMMENT 'Sempre inteiro e positivo (RN009); o sinal vem de "tipo".',
    descricao       VARCHAR(255)    NOT NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimentacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    KEY idx_movimentacao_usuario (usuario_id),
    KEY idx_movimentacao_origem (origem, origem_id),
    KEY idx_movimentacao_criado (criado_em),
    KEY idx_movimentacao_ranking (tipo, origem, criado_em)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Configurações administráveis (RN008, RN017, RN039)
-- ---------------------------------------------------------------------
-- Chave/valor genérico para parâmetros que a documentação marca como
-- configuráveis e ainda pendentes de valor definitivo (ex.: bônus de
-- primeiro descarte). Evita hardcode e permite ajuste futuro pelo Admin
-- sem alteração de schema.
CREATE TABLE IF NOT EXISTS configuracoes (
    chave           VARCHAR(80)     NOT NULL PRIMARY KEY,
    valor           VARCHAR(255)    NULL COMMENT 'NULL = configurável, ainda pendente de definição (não inventar valor).',
    descricao       VARCHAR(255)    NOT NULL,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Parceiros (RF043, RN037, RN038 — sem painel próprio no MVP)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parceiros (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150)    NOT NULL,
    descricao       VARCHAR(255)    NULL,
    contato         VARCHAR(150)    NULL,
    status          ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_parceiros_nome (nome)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Recompensas, resgates e vouchers (RF029-RF035, RN022-RN027, RN036, RN039)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recompensas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parceiro_id     INT UNSIGNED    NULL,
    nome            VARCHAR(150)    NOT NULL,
    descricao       TEXT            NULL,
    custo_pontos    INT UNSIGNED    NULL COMMENT 'PENDENTE até definição da economia de recompensas (docs/escopo.md).',
    estoque         INT             NULL COMMENT 'NULL = estoque ilimitado/não controlado.',
    validade_dias   INT UNSIGNED    NULL COMMENT 'Prazo de validade do voucher emitido a partir do resgate.',
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
    codigo          VARCHAR(30)     NOT NULL COMMENT 'Código único (RF032).',
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

-- ---------------------------------------------------------------------
-- Premiações do ranking (RF040, RF044, RN034, RN035)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS premiacoes_ranking (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    posicao         TINYINT UNSIGNED NOT NULL COMMENT '1, 2 ou 3 (RN034).',
    descricao       VARCHAR(255)    NOT NULL,
    recompensa_id   INT UNSIGNED    NULL,
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_premiacao_recompensa FOREIGN KEY (recompensa_id) REFERENCES recompensas (id) ON DELETE SET NULL,
    UNIQUE KEY uq_premiacao_posicao (posicao)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Auditoria (RNF017, RN028, UC020)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auditoria_administrativa (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT UNSIGNED    NOT NULL,
    acao            VARCHAR(80)     NOT NULL COMMENT 'Ex.: estorno_pontos, cadastro_estacao, alteracao_recompensa.',
    entidade        VARCHAR(80)     NOT NULL,
    entidade_id     INT UNSIGNED    NULL,
    justificativa   TEXT            NULL COMMENT 'Obrigatória para estornos (RN028).',
    detalhes_json   JSON            NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_admin FOREIGN KEY (admin_id) REFERENCES usuarios (id) ON DELETE RESTRICT,
    KEY idx_auditoria_entidade (entidade, entidade_id)
) ENGINE = InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
