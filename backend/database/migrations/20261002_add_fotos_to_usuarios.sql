-- Execute uma vez em bancos existentes antes de publicar a seção Conta.
ALTER TABLE usuarios
    ADD COLUMN foto_google VARCHAR(2048) NULL,
    ADD COLUMN foto_personalizada MEDIUMTEXT NULL;
