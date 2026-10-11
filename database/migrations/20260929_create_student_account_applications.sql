CREATE TABLE IF NOT EXISTS solicitudes_estudiante (
    id_solicitud_estudiante INT UNSIGNED NOT NULL AUTO_INCREMENT,
    correo VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    carnet VARCHAR(50) NOT NULL,
    telefono VARCHAR(30) NULL,
    edad INT UNSIGNED NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NULL,
    estado_solicitud ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    id_admin_revisor INT UNSIGNED NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisada_en DATETIME NULL,
    correo_pendiente VARCHAR(254)
        GENERATED ALWAYS AS (CASE WHEN estado_solicitud = 'Pendiente' THEN correo ELSE NULL END) STORED,
    carnet_pendiente VARCHAR(50)
        GENERATED ALWAYS AS (CASE WHEN estado_solicitud = 'Pendiente' THEN carnet ELSE NULL END) STORED,
    PRIMARY KEY (id_solicitud_estudiante),
    UNIQUE KEY uq_solicitudes_estudiante_correo_pendiente (correo_pendiente),
    UNIQUE KEY uq_solicitudes_estudiante_carnet_pendiente (carnet_pendiente),
    KEY ix_solicitudes_estudiante_estado (estado_solicitud, creada_en),
    KEY ix_solicitudes_estudiante_admin (id_admin_revisor),
    CONSTRAINT fk_solicitudes_estudiante_admin
        FOREIGN KEY (id_admin_revisor) REFERENCES administradores (id_admin)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
