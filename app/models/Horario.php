<?php

declare(strict_types=1);

/**
*Consulta de horarios
 * mantiendo los horarios 
 * valida los cruces entre instructores.
 */
final class Horario extends BaseModel
{
    /** Obtiene horarios con su curso y los datos del instructor asignado. */
    public function all(): array
    {
        return $this->database->query(
            'SELECT h.IdHorario, h.IdCurso, h.Dia, h.HoraInicio, h.Hora_finalizacion,
                    c.Nombre_Curso, i.IdInstructor, u.Nombres, u.Apellidos
             FROM horarios AS h
             INNER JOIN cursos AS c ON c.IdCurso = h.IdCurso
             INNER JOIN instructores AS i ON i.IdInstructor = c.IdInstructor
             INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
             ORDER BY c.Nombre_Curso,
                      FIELD(h.Dia, \'Lunes\', \'Martes\', \'Miercoles\', \'Jueves\', \'Viernes\', \'Sabado\', \'Domingo\'),
                      h.HoraInicio, h.Hora_finalizacion'
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
     * Comprueba si el horario propuesto se cruza con otro curso del instructor.
     */
    public function instructorConflict(int $courseId, string $day, string $start, string $end, ?int $excludedId = null): bool
    {
        $sql = "SELECT 1
                FROM horarios AS h
                INNER JOIN cursos AS scheduled_course ON scheduled_course.IdCurso = h.IdCurso
                INNER JOIN cursos AS selected_course ON selected_course.IdCurso = :IdCurso
                WHERE selected_course.IdInstructor = scheduled_course.IdInstructor
                  AND h.Dia = :Dia
                  AND h.HoraInicio < :Hora_finalizacion
                  AND h.Hora_finalizacion > :HoraInicio";
        $parameters = [
            'IdCurso' => $courseId,
            'Dia' => $day,
            'Hora_finalizacion' => $end,
            'HoraInicio' => $start,
        ];

        if ($excludedId !== null) {
            $sql .= ' AND h.IdHorario <> :IdHorario';
            $parameters['IdHorario'] = $excludedId;
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /** Inserta un horario asociado al curso indicado. */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO horarios (IdCurso, Dia, HoraInicio, Hora_finalizacion)
             VALUES (:IdCurso, :Dia, :HoraInicio, :Hora_finalizacion)'
        );
        $statement->execute($data);
    }

    /** Actualiza curso, día y rango horario del registro indicado. */
    public function update(int $id, array $data): bool
    {
        $statement = $this->database->prepare(
            'UPDATE horarios
             SET IdCurso = :IdCurso, Dia = :Dia,
                 HoraInicio = :HoraInicio, Hora_finalizacion = :Hora_finalizacion
             WHERE IdHorario = :IdHorario'
        );
        $data['IdHorario'] = $id;
        $statement->execute($data);

        return $this->exists($id);
    }

    /**
     * Valida y elimina el elemento seleccionado.
     */
    public function delete(int $id): bool
    {
        $statement = $this->database->prepare(
            'DELETE FROM horarios WHERE IdHorario = :IdHorario'
        );
        $statement->execute(['IdHorario' => $id]);

        return $statement->rowCount() > 0;
    }

    /** Comprueba si existe un horario por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM horarios WHERE IdHorario = :IdHorario'
        );
        $statement->execute(['IdHorario' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
