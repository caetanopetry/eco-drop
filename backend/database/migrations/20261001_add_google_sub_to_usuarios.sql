-- Adiciona vínculo opcional com uma identidade Google a contas EcoDrop existentes.
ALTER TABLE usuarios
    ADD COLUMN google_sub VARCHAR(255) NULL,
    ADD UNIQUE KEY uq_usuarios_google_sub (google_sub);
