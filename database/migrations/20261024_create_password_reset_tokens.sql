CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id_password_reset_token BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    PRIMARY KEY (id_password_reset_token),
    UNIQUE KEY uq_password_reset_token_hash (token_hash),
    KEY ix_password_reset_user_created (id_usuario, created_at),
    CONSTRAINT fk_password_reset_token_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
