ALTER TABLE solicitudes_estudiante
    DROP INDEX uq_solicitudes_estudiante_carnet_pendiente,
    DROP COLUMN carnet_pendiente,
    DROP COLUMN carnet;

ALTER TABLE instructores
    ADD COLUMN carnet VARCHAR(50) NULL AFTER id_usuario;

UPDATE instructores AS i
INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
SET i.carnet = CONCAT(
    UPPER(LEFT(CONCAT(REPLACE(TRIM(u.apellidos), ' ', ''), 'X'), 2)),
    LPAD(MOD(i.id_instructor, 10000), 4, '0')
);

ALTER TABLE instructores
    MODIFY COLUMN carnet VARCHAR(50) NOT NULL,
    ADD UNIQUE KEY uq_instructores_carnet (carnet);
