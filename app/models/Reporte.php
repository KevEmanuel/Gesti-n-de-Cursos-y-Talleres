<?php

declare(strict_types=1);

/**
 * Obtiene metricas de ocupacion y disponibilidad de cursos.
 */
final class Reporte extends BaseModel
{
    protected function tableName(): string
    {
        return 'inscripciones';
    }

    /**
     * Obtiene ocupacion y disponibilidad aplicando los filtros indicados.
     */
    public function courseOccupancy(array $filters): array
    {
        $sql = "SELECT c.id_curso, c.nombre_curso, ca.nombre AS categoria,
                       c.modalidad, c.maximo_cupos, c.estado,
                       COUNT(i.id_inscripcion) AS inscritos_activos,
                       GREATEST(c.maximo_cupos - COUNT(i.id_inscripcion), 0) AS cupos_disponibles
                FROM cursos AS c
                INNER JOIN categorias AS ca ON ca.id_categoria = c.id_categoria
                LEFT JOIN inscripciones AS i
                    ON i.id_curso = c.id_curso
                    AND i.estado_inscripcion IN ('Confirmada', 'Pendiente')";
        $conditions = [];
        $parameters = [];

        if ($filters['fecha_inicio'] !== '') {
            $conditions[] = 'i.fecha_inscripcion >= :fecha_inicio';
            $parameters['fecha_inicio'] = $filters['fecha_inicio'] . ' 00:00:00';
        }
        if ($filters['fecha_cierre'] !== '') {
            $conditions[] = 'i.fecha_inscripcion < DATE_ADD(:fecha_cierre, INTERVAL 1 DAY)';
            $parameters['fecha_cierre'] = $filters['fecha_cierre'];
        }
        if ($filters['id_categoria'] !== '') {
            $conditions[] = 'c.id_categoria = :id_categoria';
            $parameters['id_categoria'] = (int) $filters['id_categoria'];
        }
        if ($filters['modalidad'] !== '') {
            $conditions[] = 'c.modalidad = :modalidad';
            $parameters['modalidad'] = $filters['modalidad'];
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY c.id_curso, c.nombre_curso, ca.nombre, c.modalidad,
                           c.maximo_cupos, c.estado
                  ORDER BY inscritos_activos DESC, c.nombre_curso';

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * Obtiene las categorias disponibles para filtrar los reportes.
     */
    public function categories(): array
    {
        return $this->database->query(
            'SELECT id_categoria, nombre FROM categorias ORDER BY nombre'
        )->fetchAll();
    }
}
