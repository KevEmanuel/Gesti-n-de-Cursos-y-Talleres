CREATE DATABASE IF NOT EXISTS gestiondedatos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestiondedatos;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    correo VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) NULL,
    edad INT UNSIGNED NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NULL,
    estado_cuenta ENUM('Activo', 'Inactivo', 'Bloqueado') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS estudiantes (
    id_estudiante INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NOT NULL,
    carnet VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_estudiante),
    UNIQUE KEY uq_estudiantes_usuario (id_usuario),
    UNIQUE KEY uq_estudiantes_carnet (carnet),
    CONSTRAINT fk_estudiantes_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS administradores (
    id_admin INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_admin),
    UNIQUE KEY uq_administradores_usuario (id_usuario),
    CONSTRAINT fk_administradores_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitudes_estudiante (
    id_solicitud_estudiante INT UNSIGNED NOT NULL AUTO_INCREMENT,
    correo VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) NULL,
    edad INT UNSIGNED NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NULL,
    estado_solicitud ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    id_admin_revisor INT UNSIGNED NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisada_en DATETIME NULL,
    correo_pendiente VARCHAR(254)
        GENERATED ALWAYS AS (CASE WHEN estado_solicitud = 'Pendiente' THEN correo ELSE NULL END) STORED,
    PRIMARY KEY (id_solicitud_estudiante),
    UNIQUE KEY uq_solicitudes_estudiante_correo_pendiente (correo_pendiente),
    KEY ix_solicitudes_estudiante_estado (estado_solicitud, creada_en),
    KEY ix_solicitudes_estudiante_admin (id_admin_revisor),
    CONSTRAINT fk_solicitudes_estudiante_admin
        FOREIGN KEY (id_admin_revisor) REFERENCES administradores (id_admin)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS instructores (
    id_instructor INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NOT NULL,
    carnet VARCHAR(50) NOT NULL,
    especialidad VARCHAR(255) NULL,
    PRIMARY KEY (id_instructor),
    UNIQUE KEY uq_instructores_usuario (id_usuario),
    UNIQUE KEY uq_instructores_carnet (carnet),
    CONSTRAINT fk_instructores_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(500) NULL,
    -- Added because the category use case requires activation/deactivation.
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id_categoria),
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cursos (
    id_curso INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_categoria INT UNSIGNED NOT NULL,
    id_instructor INT UNSIGNED NOT NULL,
    id_admin INT UNSIGNED NOT NULL,
    nombre_curso VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    duracion VARCHAR(100) NOT NULL,
    modalidad ENUM('Presencial', 'Virtual', 'Hibrido') NOT NULL,
    requisitos TEXT NULL,
    costo_curso DECIMAL(10, 2) NOT NULL,
    maximo_cupos INT UNSIGNED NOT NULL,
    estado ENUM('Activo', 'Cerrado', 'Lleno', 'Cancelado') NOT NULL DEFAULT 'Activo',
    PRIMARY KEY (id_curso),
    KEY ix_cursos_categoria (id_categoria),
    KEY ix_cursos_instructor (id_instructor),
    KEY ix_cursos_admin (id_admin),
    CONSTRAINT fk_cursos_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cursos_instructor
        FOREIGN KEY (id_instructor) REFERENCES instructores (id_instructor)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cursos_admin
        FOREIGN KEY (id_admin) REFERENCES administradores (id_admin)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_cursos_costo CHECK (costo_curso >= 0),
    CONSTRAINT chk_cursos_capacidad CHECK (maximo_cupos > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS horarios (
    id_horario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_curso INT UNSIGNED NOT NULL,
    dia ENUM('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_finalizacion TIME NOT NULL,
    PRIMARY KEY (id_horario),
    KEY ix_horarios_curso_dia (id_curso, dia),
    CONSTRAINT fk_horarios_curso
        FOREIGN KEY (id_curso) REFERENCES cursos (id_curso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_horarios_rango CHECK (hora_finalizacion > hora_inicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS periodos_inscripcion (
    id_periodo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_curso INT UNSIGNED NOT NULL,
    id_admin INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NOT NULL,
    PRIMARY KEY (id_periodo),
    KEY ix_periodos_curso_fechas (id_curso, fecha_inicio, fecha_cierre),
    KEY ix_periodos_admin (id_admin),
    CONSTRAINT fk_periodos_curso
        FOREIGN KEY (id_curso) REFERENCES cursos (id_curso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_periodos_admin
        FOREIGN KEY (id_admin) REFERENCES administradores (id_admin)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_periodos_fechas CHECK (fecha_cierre >= fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscripciones (
    id_inscripcion INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_estudiante INT UNSIGNED NOT NULL,
    id_curso INT UNSIGNED NOT NULL,
    id_admin INT UNSIGNED NULL,
    fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_inscripcion ENUM('Confirmada', 'Pendiente', 'Cancelada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    motivo_rechazo VARCHAR(1000) NULL,
    estudiante_activo_id INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN estado_inscripcion IN ('Confirmada', 'Pendiente') THEN id_estudiante
                ELSE NULL
            END
        ) STORED,
    curso_activo_id INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN estado_inscripcion IN ('Confirmada', 'Pendiente') THEN id_curso
                ELSE NULL
            END
        ) STORED,
    PRIMARY KEY (id_inscripcion),
    UNIQUE KEY uq_inscripciones_activas (estudiante_activo_id, curso_activo_id),
    KEY ix_inscripciones_curso_estado (id_curso, estado_inscripcion),
    KEY ix_inscripciones_admin (id_admin),
    CONSTRAINT fk_inscripciones_estudiante
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inscripciones_curso
        FOREIGN KEY (id_curso) REFERENCES cursos (id_curso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inscripciones_admin
        FOREIGN KEY (id_admin) REFERENCES administradores (id_admin)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Cupos disponibles are derived from capacity and active inscriptions rather
-- than stored as a second source of truth.
CREATE OR REPLACE VIEW vw_cursos_disponibilidad AS
SELECT
    c.id_curso,
    c.maximo_cupos,
    COUNT(i.id_inscripcion) AS inscritos_activos,
    GREATEST(c.maximo_cupos - COUNT(i.id_inscripcion), 0) AS cupos_disponibles
FROM cursos AS c
LEFT JOIN inscripciones AS i
    ON i.id_curso = c.id_curso
    AND i.estado_inscripcion IN ('Confirmada', 'Pendiente')
GROUP BY c.id_curso, c.maximo_cupos;
