<?php

declare(strict_types=1);

/**
 * Obtiene metricas de ocupacion y disponibilidad de cursos.
 */
final class Reporte extends BaseModel
{
    /**
     * Obtiene ocupacion y disponibilidad aplicando los filtros indicados.
     */
    public function courseOccupancy(array $filters): array
    {
        $sql = "SELECT c.IdCurso, c.Nombre_Curso, ca.Nombre AS categoria,
                       c.Modalidad, c.Maximo_cupos, c.Estado,
                       COUNT(i.IdInscripcion) AS InscritosActivos,
                       GREATEST(c.Maximo_cupos - COUNT(i.IdInscripcion), 0) AS Cupos_Disponibles
                FROM cursos AS c
                INNER JOIN categorias AS ca ON ca.IdCategoria = c.IdCategoria
                LEFT JOIN inscripciones AS i
                    ON i.IdCurso = c.IdCurso
                    AND i.EstadoInscripcion IN ('Confirmada', 'Pendiente')";
        $conditions = [];
        $parameters = [];

        if ($filters['FechaInicio'] !== '') {
            $conditions[] = 'i.FechaInscripcion >= :FechaInicio';
            $parameters['FechaInicio'] = $filters['FechaInicio'] . ' 00:00:00';
        }
        if ($filters['FechaCierre'] !== '') {
            $conditions[] = 'i.FechaInscripcion < DATE_ADD(:FechaCierre, INTERVAL 1 DAY)';
            $parameters['FechaCierre'] = $filters['FechaCierre'];
        }
        if ($filters['IdCategoria'] !== '') {
            $conditions[] = 'c.IdCategoria = :IdCategoria';
            $parameters['IdCategoria'] = (int) $filters['IdCategoria'];
        }
        if ($filters['Modalidad'] !== '') {
            $conditions[] = 'c.Modalidad = :Modalidad';
            $parameters['Modalidad'] = $filters['Modalidad'];
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY c.IdCurso, c.Nombre_Curso, ca.Nombre, c.Modalidad,
                           c.Maximo_cupos, c.Estado
                  ORDER BY InscritosActivos DESC, c.Nombre_Curso';

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
            'SELECT IdCategoria, Nombre FROM categorias ORDER BY Nombre'
        )->fetchAll();
    }
}
