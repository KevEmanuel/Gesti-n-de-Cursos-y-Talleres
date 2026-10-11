<?php

declare(strict_types=1);

/**
 * Consulta y mantiene los horarios y valida los cruces entre instructores.
 */
final class Horario extends BaseModel
{
    protected function tableName(): string
    {
        return 'horarios';
    }

    /**
     * Obtiene los registros del modulo para su consulta.
     */
    public function all(): array
    {
        return $this->database->query(
            'SELECT h.id_horario, h.id_curso, h.dia, h.hora_inicio, h.hora_finalizacion,
                    c.nombre_curso, i.id_instructor, u.nombres, u.apellidos
             FROM horarios AS h
             INNER JOIN cursos AS c ON c.id_curso = h.id_curso
             INNER JOIN instructores AS i ON i.id_instructor = c.id_instructor
             INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
             ORDER BY c.nombre_curso, h.dia, h.hora_inicio'
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
     * Comprueba si el horario propuesto se cruza con otro curso del instructor.
     */
    public function instructorConflict(int $courseId, string $day, string $start, string $end, ?int $excludedId = null): bool
    {
        $sql = "SELECT 1
                FROM horarios AS h
                INNER JOIN cursos AS scheduled_course ON scheduled_course.id_curso = h.id_curso
                INNER JOIN cursos AS selected_course ON selected_course.id_curso = :id_curso
                WHERE selected_course.id_instructor = scheduled_course.id_instructor
                  AND h.dia = :dia
                  AND h.hora_inicio < :hora_finalizacion
                  AND h.hora_finalizacion > :hora_inicio";
        $parameters = [
            'id_curso' => $courseId,
            'dia' => $day,
            'hora_finalizacion' => $end,
            'hora_inicio' => $start,
        ];

        if ($excludedId !== null) {
            $sql .= ' AND h.id_horario <> :id_horario';
            $parameters['id_horario'] = $excludedId;
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Valida y registra un nuevo elemento del modulo.
     */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO horarios (id_curso, dia, hora_inicio, hora_finalizacion)
             VALUES (:id_curso, :dia, :hora_inicio, :hora_finalizacion)'
        );
        $statement->execute($data);
    }

    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
    public function update(int $id, array $data): bool
    {
        $statement = $this->database->prepare(
            'UPDATE horarios
             SET id_curso = :id_curso, dia = :dia,
                 hora_inicio = :hora_inicio, hora_finalizacion = :hora_finalizacion
             WHERE id_horario = :id_horario'
        );
        $data['id_horario'] = $id;
        $statement->execute($data);

        return $this->exists($id);
    }

    /**
     * Valida y elimina el elemento seleccionado y sus relaciones permitidas.
     */
    public function delete(int $id): bool
    {
        $statement = $this->database->prepare(
            'DELETE FROM horarios WHERE id_horario = :id_horario'
        );
        $statement->execute(['id_horario' => $id]);

        return $statement->rowCount() > 0;
    }

    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM horarios WHERE id_horario = :id_horario'
        );
        $statement->execute(['id_horario' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
