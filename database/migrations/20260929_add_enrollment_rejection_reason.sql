ALTER TABLE inscripciones
    MODIFY COLUMN estado_inscripcion
        ENUM('Confirmada', 'Pendiente', 'Cancelada', 'Rechazada')
        NOT NULL DEFAULT 'Pendiente',
    ADD COLUMN motivo_rechazo VARCHAR(1000) NULL AFTER estado_inscripcion;
