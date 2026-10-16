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
