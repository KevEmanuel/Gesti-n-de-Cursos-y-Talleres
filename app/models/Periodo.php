<?php

declare(strict_types=1);

/**
 * Consulta y mantiene los periodos de inscripcion asociados a cursos.
 */
final class Periodo extends BaseModel
{
    /** Obtiene períodos con curso asociado y estado calculado según sus fechas. */
    public function all(): array
    {
        return $this->database->query(
            'SELECT p.id_periodo, p.id_curso, p.fecha_inicio, p.fecha_cierre,
                    c.nombre_curso, a.id_admin,
                    CASE
                        WHEN CURRENT_DATE < p.fecha_inicio THEN "Programado"
                        WHEN CURRENT_DATE > p.fecha_cierre THEN "Cerrado"
                        ELSE "Vigente"
                    END AS estado_periodo
             FROM periodos_inscripcion AS p
             INNER JOIN cursos AS c ON c.id_curso = p.id_curso
             INNER JOIN administradores AS a ON a.id_admin = p.id_admin
             ORDER BY p.fecha_inicio DESC'
        )->fetchAll();
    }

    /**
     * Obtiene los cursos validos para asociarlos al modulo.
     */
    public function courses(): array
    {
        return $this->database->query(
            "SELECT id_curso, nombre_curso FROM cursos
             WHERE estado IN ('Activo', 'Cerrado')
             ORDER BY nombre_curso"
        )->fetchAll();
    }

    /**
     * Comprueba si el periodo se solapa con otro del mismo curso.
     */
    public function hasOverlappingPeriod(int $courseId, string $start, string $end, ?int $excludedId = null): bool
    {
        $sql = 'SELECT 1 FROM periodos_inscripcion
                WHERE id_curso = :id_curso
                  AND fecha_inicio <= :fecha_cierre
                  AND fecha_cierre >= :fecha_inicio';
        $parameters = [
            'id_curso' => $courseId,
            'fecha_cierre' => $end,
            'fecha_inicio' => $start,
        ];

        if ($excludedId !== null) {
            $sql .= ' AND id_periodo <> :id_periodo';
            $parameters['id_periodo'] = $excludedId;
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
                (id_curso, id_admin, fecha_inicio, fecha_cierre)
             VALUES
                (:id_curso, :id_admin, :fecha_inicio, :fecha_cierre)'
        );
        $statement->execute($data);
    }

    /** Actualiza curso y fechas del período seleccionado. */
    public function update(int $id, array $data): bool
    {
        $statement = $this->database->prepare(
            'UPDATE periodos_inscripcion
             SET id_curso = :id_curso, fecha_inicio = :fecha_inicio, fecha_cierre = :fecha_cierre
             WHERE id_periodo = :id_periodo'
        );
        $data['id_periodo'] = $id;
        $statement->execute($data);

        return $this->exists($id);
    }

    /**
     * Valida y elimina el elemento seleccionado y sus relaciones permitidas.
     */
    public function delete(int $id): bool
    {
        $statement = $this->database->prepare(
            'DELETE FROM periodos_inscripcion WHERE id_periodo = :id_periodo'
        );
        $statement->execute(['id_periodo' => $id]);

        return $statement->rowCount() > 0;
    }

    /** Comprueba si existe un período de inscripción por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM periodos_inscripcion WHERE id_periodo = :id_periodo'
        );
        $statement->execute(['id_periodo' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
