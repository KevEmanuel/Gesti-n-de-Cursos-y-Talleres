-- Preparado para la estructura del respaldo gestiondedatos (2).sql
-- El respaldo indica MariaDB 10.4.32: se usa CHANGE COLUMN, no RENAME COLUMN.
-- Haz un respaldo antes de ejecutar. Ejecuta este archivo una sola vez sobre
-- la base gestiondedatos seleccionada en phpMyAdmin.

ALTER TABLE usuarios
    CHANGE COLUMN correo Correo VARCHAR(254) NOT NULL,
    CHANGE COLUMN nombres Nombres VARCHAR(100) NOT NULL,
    CHANGE COLUMN apellidos Apellidos VARCHAR(100) NOT NULL,
    CHANGE COLUMN telefono Telefono VARCHAR(30) NULL,
    CHANGE COLUMN edad Edad INT(10) UNSIGNED NULL,
    CHANGE COLUMN genero Genero ENUM('Masculino', 'Femenino', 'Otro') NULL,
    CHANGE COLUMN estado_cuenta Estado_Cuenta
        ENUM('Activo', 'Inactivo', 'Bloqueado') NOT NULL DEFAULT 'Activo';

ALTER TABLE estudiantes
    CHANGE COLUMN carnet Carnet VARCHAR(50) NOT NULL;

ALTER TABLE instructores
    CHANGE COLUMN carnet Carnet VARCHAR(50) NOT NULL,
    CHANGE COLUMN especialidad Especialidad VARCHAR(255) NULL;

ALTER TABLE categorias
    CHANGE COLUMN nombre Nombre VARCHAR(150) NOT NULL,
    CHANGE COLUMN descripcion Descripcion VARCHAR(500) NULL,
    CHANGE COLUMN estado Estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo';

ALTER TABLE cursos
    CHANGE COLUMN nombre_curso Nombre_Curso VARCHAR(200) NOT NULL,
    CHANGE COLUMN descripcion Descripcion TEXT NULL,
    CHANGE COLUMN duracion Duracion VARCHAR(100) NOT NULL,
    CHANGE COLUMN modalidad Modalidad ENUM('Presencial', 'Virtual', 'Hibrido') NOT NULL,
    CHANGE COLUMN requisitos Requisitos TEXT NULL,
    CHANGE COLUMN costo_curso Costo_curso DECIMAL(10, 2) NOT NULL,
    CHANGE COLUMN maximo_cupos Maximo_cupos INT(10) UNSIGNED NOT NULL,
    CHANGE COLUMN estado Estado
        ENUM('Activo', 'Cerrado', 'Lleno', 'Cancelado') NOT NULL DEFAULT 'Activo';

ALTER TABLE horarios
    CHANGE COLUMN dia Dia
        ENUM('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo') NOT NULL,
    CHANGE COLUMN hora_finalizacion Hora_finalizacion TIME NOT NULL;

ALTER TABLE inscripciones
    DROP INDEX uq_inscripciones_activas,
    DROP COLUMN estudiante_activo_id,
    DROP COLUMN curso_activo_id;

ALTER TABLE inscripciones
    CHANGE COLUMN estado_inscripcion EstadoInscripcion
        ENUM('Confirmada', 'Pendiente', 'Cancelada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    CHANGE COLUMN MotivoRechazo MotivoRechazo VARCHAR(1000) NULL;

ALTER TABLE inscripciones
    ADD COLUMN EstudianteActivoId INT(10) UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN EstadoInscripcion IN ('Confirmada', 'Pendiente') THEN IdEstudiante
                ELSE NULL
            END
        ) STORED,
    ADD COLUMN CursoActivoId INT(10) UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN EstadoInscripcion IN ('Confirmada', 'Pendiente') THEN IdCurso
                ELSE NULL
            END
        ) STORED,
    ADD UNIQUE KEY uq_inscripciones_activas (EstudianteActivoId, CursoActivoId);

ALTER TABLE solicitudes_estudiante
    DROP INDEX uq_solicitudes_estudiante_correo_pendiente,
    DROP COLUMN correo_pendiente;

ALTER TABLE solicitudes_estudiante
    CHANGE COLUMN id_solicitud_estudiante IdSolicitudEstudiante INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN correo Correo VARCHAR(254) NOT NULL,
    CHANGE COLUMN password_hash Password VARCHAR(255) NULL,
    CHANGE COLUMN nombres Nombres VARCHAR(100) NOT NULL,
    CHANGE COLUMN apellidos Apellidos VARCHAR(100) NOT NULL,
    CHANGE COLUMN telefono Telefono VARCHAR(30) NULL,
    CHANGE COLUMN edad Edad INT(10) UNSIGNED NULL,
    CHANGE COLUMN genero Genero ENUM('Masculino', 'Femenino', 'Otro') NULL,
    CHANGE COLUMN estado_solicitud EstadoSolicitud
        ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    CHANGE COLUMN id_admin_revisor IdAdminRevisor INT(10) UNSIGNED NULL,
    CHANGE COLUMN creada_en CreadaEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    CHANGE COLUMN revisada_en RevisadaEn DATETIME NULL;

ALTER TABLE solicitudes_estudiante
    ADD COLUMN CorreoPendiente VARCHAR(254)
        GENERATED ALWAYS AS (
            CASE WHEN EstadoSolicitud = 'Pendiente' THEN Correo ELSE NULL END
        ) STORED,
    ADD UNIQUE KEY uq_solicitudes_estudiante_correo_pendiente (CorreoPendiente);

-- El respaldo no contiene esta tabla, necesaria para recuperar contraseñas.
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    IdPasswordResetToken BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdUsuario INT(10) UNSIGNED NOT NULL,
    TokenHash CHAR(64) NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    ExpiresAt DATETIME NOT NULL,
    UsedAt DATETIME NULL,
    PRIMARY KEY (IdPasswordResetToken),
    UNIQUE KEY uq_password_reset_token_hash (TokenHash),
    KEY ix_password_reset_user_created (IdUsuario, CreatedAt),
    CONSTRAINT fk_password_reset_token_usuario
        FOREIGN KEY (IdUsuario) REFERENCES usuarios (IdUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW vw_cursos_disponibilidad AS
SELECT
    c.IdCurso,
    c.Maximo_cupos,
    COUNT(i.IdInscripcion) AS InscritosActivos,
    GREATEST(c.Maximo_cupos - COUNT(i.IdInscripcion), 0) AS Cupos_Disponibles
FROM cursos AS c
LEFT JOIN inscripciones AS i
    ON i.IdCurso = c.IdCurso
    AND i.EstadoInscripcion IN ('Confirmada', 'Pendiente')
GROUP BY c.IdCurso, c.Maximo_cupos;
