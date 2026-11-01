CREATE DATABASE IF NOT EXISTS gestiondedatos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestiondedatos;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    IdUsuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Correo VARCHAR(254) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Nombres VARCHAR(100) NOT NULL,
    Apellidos VARCHAR(100) NOT NULL,
    Telefono VARCHAR(30) NULL,
    Edad INT UNSIGNED NULL,
    Genero ENUM('Masculino', 'Femenino', 'Otro') NULL,
    Estado_Cuenta ENUM('Activo', 'Inactivo', 'Bloqueado') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (IdUsuario),
    UNIQUE KEY uq_usuarios_correo (Correo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    IdPasswordResetToken BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdUsuario INT UNSIGNED NOT NULL,
    TokenHash CHAR(64) NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ExpiresAt DATETIME NOT NULL,
    UsedAt DATETIME NULL,
    PRIMARY KEY (IdPasswordResetToken),
    UNIQUE KEY uq_password_reset_token_hash (TokenHash),
    KEY ix_password_reset_user_created (IdUsuario, CreatedAt),
    CONSTRAINT fk_password_reset_token_usuario
        FOREIGN KEY (IdUsuario) REFERENCES usuarios (IdUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS estudiantes (
    IdEstudiante INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdUsuario INT UNSIGNED NOT NULL,
    Carnet VARCHAR(50) NOT NULL,
    PRIMARY KEY (IdEstudiante),
    UNIQUE KEY uq_estudiantes_usuario (IdUsuario),
    UNIQUE KEY uq_estudiantes_carnet (Carnet),
    CONSTRAINT fk_estudiantes_usuario
        FOREIGN KEY (IdUsuario) REFERENCES usuarios (IdUsuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS administradores (
    IdAdmin INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdUsuario INT UNSIGNED NOT NULL,
    PRIMARY KEY (IdAdmin),
    UNIQUE KEY uq_administradores_usuario (IdUsuario),
    CONSTRAINT fk_administradores_usuario
        FOREIGN KEY (IdUsuario) REFERENCES usuarios (IdUsuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitudes_estudiante (
    IdSolicitudEstudiante INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Correo VARCHAR(254) NOT NULL,
    Password VARCHAR(255) NULL,
    Nombres VARCHAR(100) NOT NULL,
    Apellidos VARCHAR(100) NOT NULL,
    Telefono VARCHAR(30) NULL,
    Edad INT UNSIGNED NULL,
    Genero ENUM('Masculino', 'Femenino', 'Otro') NULL,
    EstadoSolicitud ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    IdAdminRevisor INT UNSIGNED NULL,
    CreadaEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    RevisadaEn DATETIME NULL,
    CorreoPendiente VARCHAR(254)
        GENERATED ALWAYS AS (CASE WHEN EstadoSolicitud = 'Pendiente' THEN Correo ELSE NULL END) STORED,
    PRIMARY KEY (IdSolicitudEstudiante),
    UNIQUE KEY uq_solicitudes_estudiante_correo_pendiente (CorreoPendiente),
    KEY ix_solicitudes_estudiante_estado (EstadoSolicitud, CreadaEn),
    KEY ix_solicitudes_estudiante_admin (IdAdminRevisor),
    CONSTRAINT fk_solicitudes_estudiante_admin
        FOREIGN KEY (IdAdminRevisor) REFERENCES administradores (IdAdmin)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS instructores (
    IdInstructor INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdUsuario INT UNSIGNED NOT NULL,
    Carnet VARCHAR(50) NOT NULL,
    Especialidad VARCHAR(255) NULL,
    PRIMARY KEY (IdInstructor),
    UNIQUE KEY uq_instructores_usuario (IdUsuario),
    UNIQUE KEY uq_instructores_carnet (Carnet),
    CONSTRAINT fk_instructores_usuario
        FOREIGN KEY (IdUsuario) REFERENCES usuarios (IdUsuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    IdCategoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Nombre VARCHAR(150) NOT NULL,
    Descripcion VARCHAR(500) NULL,
    -- Added because the category use case requires activation/deactivation.
    Estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (IdCategoria),
    UNIQUE KEY uq_categorias_nombre (Nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cursos (
    IdCurso INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdCategoria INT UNSIGNED NOT NULL,
    IdInstructor INT UNSIGNED NOT NULL,
    IdAdmin INT UNSIGNED NOT NULL,
    Nombre_Curso VARCHAR(200) NOT NULL,
    Descripcion TEXT NULL,
    Duracion VARCHAR(100) NOT NULL,
    Modalidad ENUM('Presencial', 'Virtual', 'Hibrido') NOT NULL,
    Requisitos TEXT NULL,
    Costo_curso DECIMAL(10, 2) NOT NULL,
    Maximo_cupos INT UNSIGNED NOT NULL,
    Estado ENUM('Activo', 'Cerrado', 'Lleno', 'Cancelado') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (IdCurso),
    KEY ix_cursos_categoria (IdCategoria),
    KEY ix_cursos_instructor (IdInstructor),
    KEY ix_cursos_admin (IdAdmin),
    CONSTRAINT fk_cursos_categoria
        FOREIGN KEY (IdCategoria) REFERENCES categorias (IdCategoria)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cursos_instructor
        FOREIGN KEY (IdInstructor) REFERENCES instructores (IdInstructor)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cursos_admin
        FOREIGN KEY (IdAdmin) REFERENCES administradores (IdAdmin)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_cursos_costo CHECK (Costo_curso >= 0),
    CONSTRAINT chk_cursos_capacidad CHECK (Maximo_cupos > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS horarios (
    IdHorario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdCurso INT UNSIGNED NOT NULL,
    Dia ENUM('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo') NOT NULL,
    HoraInicio TIME NOT NULL,
    Hora_finalizacion TIME NOT NULL,
    PRIMARY KEY (IdHorario),
    KEY ix_horarios_curso_dia (IdCurso, Dia),
    CONSTRAINT fk_horarios_curso
        FOREIGN KEY (IdCurso) REFERENCES cursos (IdCurso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_horarios_rango CHECK (Hora_finalizacion > HoraInicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS periodos_inscripcion (
    IdPeriodo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdCurso INT UNSIGNED NOT NULL,
    IdAdmin INT UNSIGNED NOT NULL,
    FechaInicio DATE NOT NULL,
    FechaCierre DATE NOT NULL,
    PRIMARY KEY (IdPeriodo),
    KEY ix_periodos_curso_fechas (IdCurso, FechaInicio, FechaCierre),
    KEY ix_periodos_admin (IdAdmin),
    CONSTRAINT fk_periodos_curso
        FOREIGN KEY (IdCurso) REFERENCES cursos (IdCurso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_periodos_admin
        FOREIGN KEY (IdAdmin) REFERENCES administradores (IdAdmin)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_periodos_fechas CHECK (FechaCierre >= FechaInicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscripciones (
    IdInscripcion INT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdEstudiante INT UNSIGNED NOT NULL,
    IdCurso INT UNSIGNED NOT NULL,
    IdAdmin INT UNSIGNED NULL,
    FechaInscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    EstadoInscripcion ENUM('Confirmada', 'Pendiente', 'Cancelada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    MotivoRechazo VARCHAR(1000) NULL,
    EstudianteActivoId INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN EstadoInscripcion IN ('Confirmada', 'Pendiente') THEN IdEstudiante
                ELSE NULL
            END
        ) STORED,
    CursoActivoId INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN EstadoInscripcion IN ('Confirmada', 'Pendiente') THEN IdCurso
                ELSE NULL
            END
        ) STORED,
    PRIMARY KEY (IdInscripcion),
    UNIQUE KEY uq_inscripciones_activas (EstudianteActivoId, CursoActivoId),
    KEY ix_inscripciones_curso_estado (IdCurso, EstadoInscripcion),
    KEY ix_inscripciones_admin (IdAdmin),
    CONSTRAINT fk_inscripciones_estudiante
        FOREIGN KEY (IdEstudiante) REFERENCES estudiantes (IdEstudiante)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inscripciones_curso
        FOREIGN KEY (IdCurso) REFERENCES cursos (IdCurso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inscripciones_admin
        FOREIGN KEY (IdAdmin) REFERENCES administradores (IdAdmin)
        ON UPDATE CASCADE ON DELETE RESTRICT
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
