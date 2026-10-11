<?php

declare(strict_types=1);

/**
 * Consulta y mantiene instructores, sus cursos asignados y su personal inscrito.
 */
final class Instructor extends BaseModel
{
    protected function tableName(): string
    {
        return 'instructores';
    }

    /**
     * Obtiene los registros del modulo para su consulta.
     */
    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT i.id_instructor, i.carnet, i.especialidad, u.id_usuario, u.correo, u.nombres,
                    u.apellidos, u.telefono, u.estado_cuenta,
                    (SELECT COUNT(*) FROM cursos AS c WHERE c.id_instructor = i.id_instructor)
                        AS cursos_asignados
             FROM instructores AS i
             INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
             ORDER BY u.apellidos, u.nombres'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene los cursos asignados al instructor junto con sus horarios.
     */
    public function coursesWithSchedules(int $instructorId): array
    {
        $statement = $this->database->prepare(
            'SELECT c.id_curso, c.nombre_curso, c.descripcion, c.duracion, c.modalidad,
                    c.requisitos, c.costo_curso, c.estado, ca.nombre AS categoria,
                    h.dia, h.hora_inicio, h.hora_finalizacion
             FROM cursos AS c
             INNER JOIN categorias AS ca ON ca.id_categoria = c.id_categoria
             LEFT JOIN horarios AS h ON h.id_curso = c.id_curso
             WHERE c.id_instructor = :id_instructor
             ORDER BY c.nombre_curso,
                      FIELD(h.dia, \'Lunes\', \'Martes\', \'Miercoles\', \'Jueves\', \'Viernes\', \'Sabado\', \'Domingo\'),
                      h.hora_inicio'
        );
        $statement->execute(['id_instructor' => $instructorId]);
        $courses = [];

        foreach ($statement->fetchAll() as $row) {
            $courseId = (int) $row['id_curso'];
            if (!isset($courses[$courseId])) {
                $courses[$courseId] = [
                    'id_curso' => $courseId,
                    'nombre_curso' => $row['nombre_curso'],
                    'descripcion' => $row['descripcion'],
                    'duracion' => $row['duracion'],
                    'modalidad' => $row['modalidad'],
                    'requisitos' => $row['requisitos'],
                    'costo_curso' => $row['costo_curso'],
                    'estado' => $row['estado'],
                    'categoria' => $row['categoria'],
                    'horarios' => [],
                    'estudiantes' => [],
                ];
            }

            if ($row['dia'] !== null) {
                $courses[$courseId]['horarios'][] = [
                    'dia' => $row['dia'],
                    'hora_inicio' => $row['hora_inicio'],
                    'hora_finalizacion' => $row['hora_finalizacion'],
                ];
            }
        }

        return array_values($courses);
    }

    /**
     * Obtiene los estudiantes con inscripcion confirmada en los cursos del instructor.
     */
    public function confirmedStudentsByCourse(int $instructorId): array
    {
        $statement = $this->database->prepare(
            "SELECT c.id_curso, e.carnet, u.nombres, u.apellidos, i.fecha_inscripcion
             FROM cursos AS c
             INNER JOIN inscripciones AS i ON i.id_curso = c.id_curso
             INNER JOIN estudiantes AS e ON e.id_estudiante = i.id_estudiante
             INNER JOIN usuarios AS u ON u.id_usuario = e.id_usuario
             WHERE c.id_instructor = :id_instructor
               AND i.estado_inscripcion = 'Confirmada'
             ORDER BY c.nombre_curso, u.apellidos, u.nombres"
        );
        $statement->execute(['id_instructor' => $instructorId]);
        $studentsByCourse = [];

        foreach ($statement->fetchAll() as $row) {
            $courseId = (int) $row['id_curso'];
            $studentsByCourse[$courseId][] = [
                'carnet' => $row['carnet'],
                'nombres' => $row['nombres'],
                'apellidos' => $row['apellidos'],
                'fecha_inscripcion' => $row['fecha_inscripcion'],
            ];
        }

        return $studentsByCourse;
    }

    /**
     * Valida y registra un nuevo elemento del modulo.
     */
    public function create(array $data): void
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'INSERT INTO usuarios
                    (correo, password_hash, nombres, apellidos, telefono)
                 VALUES
                    (:correo, :password_hash, :nombres, :apellidos, :telefono)'
            );
            $statement->execute([
                'correo' => $data['correo'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'],
            ]);

            $statement = $this->database->prepare(
                'INSERT INTO instructores (id_usuario, carnet, especialidad)
                 VALUES (:id_usuario, :carnet, :especialidad)'
            );
            $statement->execute([
                'id_usuario' => (int) $this->database->lastInsertId(),
                'carnet' => Identificador::generarCarnet(
                    $this->database,
                    (string) $data['apellidos'],
                    'instructores'
                ),
                'especialidad' => $data['especialidad'],
            ]);

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
    public function update(int $id, array $data): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT i.carnet, u.apellidos
                 FROM instructores AS i
                 INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
                 WHERE i.id_instructor = :id_instructor
                 FOR UPDATE'
            );
            $statement->execute(['id_instructor' => $id]);
            $current = $statement->fetch();
            if (!is_array($current)) {
                $this->database->rollBack();
                return false;
            }

            $carnet = $current['apellidos'] !== $data['apellidos']
                ? Identificador::generarCarnet($this->database, (string) $data['apellidos'], 'instructores')
                : $current['carnet'];
            $statement = $this->database->prepare(
                'UPDATE usuarios AS u
                 INNER JOIN instructores AS i ON i.id_usuario = u.id_usuario
                 SET u.correo = :correo, u.nombres = :nombres, u.apellidos = :apellidos,
                     u.telefono = :telefono, i.especialidad = :especialidad, i.carnet = :carnet
                 WHERE i.id_instructor = :id_instructor'
            );
            $statement->execute([
                'correo' => $data['correo'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'],
                'especialidad' => $data['especialidad'],
                'carnet' => $carnet,
                'id_instructor' => $id,
            ]);
            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Valida y elimina el elemento seleccionado y sus relaciones permitidas.
     */
    public function delete(int $id, ?int $replacementId): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT id_usuario FROM instructores
                 WHERE id_instructor = :id_instructor
                 FOR UPDATE'
            );
            $statement->execute(['id_instructor' => $id]);
            $userId = $statement->fetchColumn();
            if ($userId === false) {
                $this->database->rollBack();
                return false;
            }

            $statement = $this->database->prepare(
                'SELECT COUNT(*) FROM cursos WHERE id_instructor = :id_instructor'
            );
            $statement->execute(['id_instructor' => $id]);
            $courseCount = (int) $statement->fetchColumn();
            if ($courseCount > 0) {
                if ($replacementId === null || $replacementId === $id) {
                    throw new DomainException('Selecciona un instructor de reemplazo para reasignar sus cursos antes de eliminarlo.');
                }

                $this->reassignCourses($id, $replacementId);
            }

            $statement = $this->database->prepare(
                'DELETE FROM instructores WHERE id_instructor = :id_instructor'
            );
            $statement->execute(['id_instructor' => $id]);
            $statement = $this->database->prepare(
                'DELETE FROM usuarios WHERE id_usuario = :id_usuario'
            );
            $statement->execute(['id_usuario' => (int) $userId]);
            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Actualiza el estado del registro y aplica las reglas del modulo.
     */
    public function setStatus(int $id, string $status, ?int $replacementId = null): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT i.id_usuario
                 FROM instructores AS i
                 WHERE i.id_instructor = :id_instructor
                 FOR UPDATE'
            );
            $statement->execute(['id_instructor' => $id]);
            $userId = $statement->fetchColumn();
            if ($userId === false) {
                $this->database->rollBack();
                return false;
            }

            if ($status === 'Inactivo') {
                $statement = $this->database->prepare(
                    'SELECT COUNT(*) FROM cursos WHERE id_instructor = :id_instructor'
                );
                $statement->execute(['id_instructor' => $id]);
                $courseCount = (int) $statement->fetchColumn();

                if ($courseCount > 0) {
                    if ($replacementId === null) {
                        throw new DomainException(
                            'Selecciona un instructor activo para reasignar los cursos antes de desactivar esta cuenta.'
                        );
                    }

                    $this->reassignCourses($id, $replacementId);
                }
            }

            $statement = $this->database->prepare(
                'UPDATE usuarios AS u
                 INNER JOIN instructores AS i ON i.id_usuario = u.id_usuario
                 SET u.estado_cuenta = :estado_cuenta
                 WHERE i.id_instructor = :id_instructor'
            );
            $statement->execute([
                'estado_cuenta' => $status,
                'id_instructor' => $id,
            ]);
            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    private function reassignCourses(int $instructorId, int $replacementId): void
    {
        if ($replacementId === $instructorId) {
            throw new DomainException('Selecciona otro instructor activo para reasignar los cursos.');
        }

        $statement = $this->database->prepare(
            "SELECT i.id_instructor
             FROM instructores AS i
             INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
             WHERE i.id_instructor = :id_instructor
               AND u.estado_cuenta = 'Activo'
             FOR UPDATE"
        );
        $statement->execute(['id_instructor' => $replacementId]);
        if ($statement->fetchColumn() === false) {
            throw new DomainException('El instructor de reemplazo debe existir y estar activo.');
        }

        $statement = $this->database->prepare(
            'UPDATE cursos
             SET id_instructor = :replacement_id
             WHERE id_instructor = :id_instructor'
        );
        $statement->execute([
            'replacement_id' => $replacementId,
            'id_instructor' => $instructorId,
        ]);
    }

    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM instructores WHERE id_instructor = :id_instructor'
        );
        $statement->execute(['id_instructor' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
