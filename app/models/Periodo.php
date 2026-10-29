<?php

declare(strict_types=1);

/**
 * Consulta y mantiene los periodos de inscripcion asociados a cursos.
 */
final class Periodo extends BaseModel
{
    /** Obtiene períodos con curso asociado y Estado calculado según sus fechas. */
    public function all(): array
    {
        return $this->database->query(
            'SELECT p.IdPeriodo, p.IdCurso, p.FechaInicio, p.FechaCierre,
                    c.Nombre_Curso, a.IdAdmin,
                    CASE
                        WHEN CURRENT_DATE < p.FechaInicio THEN "Programado"
                        WHEN CURRENT_DATE > p.FechaCierre THEN "Cerrado"
                        ELSE "Vigente"
                    END AS estado_periodo
             FROM periodos_inscripcion AS p
             INNER JOIN cursos AS c ON c.IdCurso = p.IdCurso
             INNER JOIN administradores AS a ON a.IdAdmin = p.IdAdmin
             ORDER BY p.FechaInicio DESC'
        )->fetchAll();
    }

    /**
     * Obtiene los cursos validos para asociarlos al modulo.
     */
    public function courses(): array
    {
        return $this->database->query(
            "SELECT IdCurso, Nombre_Curso FROM cursos
             WHERE Estado IN ('Activo', 'Cerrado')
             ORDER BY Nombre_Curso"
        )->fetchAll();
    }

    /**
     * Comprueba si el periodo se solapa con otro del mismo curso.
     */
    public function hasOverlappingPeriod(int $courseId, string $start, string $end, ?int $excludedId = null): bool
    {
        $sql = 'SELECT 1 FROM periodos_inscripcion
                WHERE IdCurso = :IdCurso
                  AND FechaInicio <= :FechaCierre
                  AND FechaCierre >= :FechaInicio';
        $parameters = [
            'IdCurso' => $courseId,
            'FechaCierre' => $end,
            'FechaInicio' => $start,
        ];

        if ($excludedId !== null) {
            $sql .= ' AND IdPeriodo <> :IdPeriodo';
            $parameters['IdPeriodo'] = $excludedId;
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /** Crea un período de inscripción para el curso y administrador indicados. */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO periodos_inscripcion
                (IdCurso, IdAdmin, FechaInicio, FechaCierre)
             VALUES
                (:IdCurso, :IdAdmin, :FechaInicio, :FechaCierre)'
        );
        $statement->execute($data);
    }

    /** Actualiza curso y fechas del período seleccionado. */
    public function update(int $id, array $data): bool
    {
        $statement = $this->database->prepare(
            'UPDATE periodos_inscripcion
             SET IdCurso = :IdCurso, FechaInicio = :FechaInicio, FechaCierre = :FechaCierre
             WHERE IdPeriodo = :IdPeriodo'
        );
        $data['IdPeriodo'] = $id;
        $statement->execute($data);

        return $this->exists($id);
    }

    /**
     * Valida y elimina el elemento seleccionado y sus relaciones permitidas.
     */
    public function delete(int $id): bool
    {
        $statement = $this->database->prepare(
            'DELETE FROM periodos_inscripcion WHERE IdPeriodo = :IdPeriodo'
        );
        $statement->execute(['IdPeriodo' => $id]);

        return $statement->rowCount() > 0;
    }

    /** Comprueba si existe un período de inscripción por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM periodos_inscripcion WHERE IdPeriodo = :IdPeriodo'
        );
        $statement->execute(['IdPeriodo' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
