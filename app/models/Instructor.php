<?php

declare(strict_types=1);

/**
 * Consulta y mantiene instructores, sus cursos asignados.
 */
final class Instructor extends BaseModel
{
    /** Obtiene instructores y el número de cursos asignados a cada uno. */
    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT i.IdInstructor, i.Carnet, i.Especialidad, u.IdUsuario, u.Correo, u.Nombres,
                    u.Apellidos, u.Telefono, u.Estado_Cuenta,
                    (SELECT COUNT(*) FROM cursos AS c WHERE c.IdInstructor = i.IdInstructor)
                        AS cursos_asignados
             FROM instructores AS i
             INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
             ORDER BY u.Apellidos, u.Nombres'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene los cursos asignados al instructor junto con sus horarios.
     */
    public function coursesWithSchedules(int $instructorId): array
    {
        $statement = $this->database->prepare(
            'SELECT c.IdCurso, c.Nombre_Curso, c.Descripcion, c.Duracion, c.Modalidad,
                    c.Requisitos, c.Costo_curso, c.Estado, ca.Nombre AS categoria,
                    h.Dia, h.HoraInicio, h.Hora_finalizacion
             FROM cursos AS c
             INNER JOIN categorias AS ca ON ca.IdCategoria = c.IdCategoria
             LEFT JOIN horarios AS h ON h.IdCurso = c.IdCurso
             WHERE c.IdInstructor = :IdInstructor
             ORDER BY c.Nombre_Curso,
                      FIELD(h.Dia, \'Lunes\', \'Martes\', \'Miercoles\', \'Jueves\', \'Viernes\', \'Sabado\', \'Domingo\'),
                      h.HoraInicio'
        );
        $statement->execute(['IdInstructor' => $instructorId]);
        $courses = [];

        foreach ($statement->fetchAll() as $row) {
            $courseId = (int) $row['IdCurso'];
            if (!isset($courses[$courseId])) {
                $courses[$courseId] = [
                    'IdCurso' => $courseId,
                    'Nombre_Curso' => $row['Nombre_Curso'],
                    'Descripcion' => $row['Descripcion'],
                    'Duracion' => $row['Duracion'],
                    'Modalidad' => $row['Modalidad'],
                    'Requisitos' => $row['Requisitos'],
                    'Costo_curso' => $row['Costo_curso'],
                    'Estado' => $row['Estado'],
                    'categoria' => $row['categoria'],
                    'horarios' => [],
                    'estudiantes' => [],
                ];
            }

            if ($row['Dia'] !== null) {
                $courses[$courseId]['horarios'][] = [
                    'Dia' => $row['Dia'],
                    'HoraInicio' => $row['HoraInicio'],
                    'Hora_finalizacion' => $row['Hora_finalizacion'],
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
            "SELECT c.IdCurso, e.Carnet, u.Nombres, u.Apellidos, i.FechaInscripcion
             FROM cursos AS c
             INNER JOIN inscripciones AS i ON i.IdCurso = c.IdCurso
             INNER JOIN estudiantes AS e ON e.IdEstudiante = i.IdEstudiante
             INNER JOIN usuarios AS u ON u.IdUsuario = e.IdUsuario
             WHERE c.IdInstructor = :IdInstructor
               AND i.EstadoInscripcion = 'Confirmada'
             ORDER BY c.Nombre_Curso, u.Apellidos, u.Nombres"
        );
        $statement->execute(['IdInstructor' => $instructorId]);
        $studentsByCourse = [];

        foreach ($statement->fetchAll() as $row) {
            $courseId = (int) $row['IdCurso'];
            $studentsByCourse[$courseId][] = [
                'Carnet' => $row['Carnet'],
                'Nombres' => $row['Nombres'],
                'Apellidos' => $row['Apellidos'],
                'FechaInscripcion' => $row['FechaInscripcion'],
            ];
        }

        return $studentsByCourse;
    }

    /** Crea la cuenta y el perfil del instructor y genera su Carnet. */
    public function create(array $data): void
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'INSERT INTO usuarios
                    (Correo, Password, Nombres, Apellidos, Telefono)
                 VALUES
                    (:Correo, :Password, :Nombres, :Apellidos, :Telefono)'
            );
            $statement->execute([
                'Correo' => $data['Correo'],
                'Password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'Nombres' => $data['Nombres'],
                'Apellidos' => $data['Apellidos'],
                'Telefono' => $data['Telefono'],
            ]);

            $statement = $this->database->prepare(
                'INSERT INTO instructores (IdUsuario, Carnet, Especialidad)
                 VALUES (:IdUsuario, :Carnet, :Especialidad)'
            );
            $statement->execute([
                'IdUsuario' => (int) $this->database->lastInsertId(),
                'Carnet' => Identificador::generarCarnet(
                    $this->database,
                    (string) $data['Apellidos'],
                    'instructores'
                ),
                'Especialidad' => $data['Especialidad'],
            ]);

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /** Actualiza datos personales y Especialidad del instructor en una transacción. */
    public function update(int $id, array $data): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT i.Carnet, u.Apellidos
                 FROM instructores AS i
                 INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
                 WHERE i.IdInstructor = :IdInstructor
                 FOR UPDATE'
            );
            $statement->execute(['IdInstructor' => $id]);
            $current = $statement->fetch();
            if (!is_array($current)) {
                $this->database->rollBack();
                return false;
            }

            $Carnet = $current['Apellidos'] !== $data['Apellidos']
                ? Identificador::generarCarnet($this->database, (string) $data['Apellidos'], 'instructores')
                : $current['Carnet'];
            $statement = $this->database->prepare(
                'UPDATE usuarios AS u
                 INNER JOIN instructores AS i ON i.IdUsuario = u.IdUsuario
                 SET u.Correo = :Correo, u.Nombres = :Nombres, u.Apellidos = :Apellidos,
                     u.Telefono = :Telefono, i.Especialidad = :Especialidad, i.Carnet = :Carnet
                 WHERE i.IdInstructor = :IdInstructor'
            );
            $statement->execute([
                'Correo' => $data['Correo'],
                'Nombres' => $data['Nombres'],
                'Apellidos' => $data['Apellidos'],
                'Telefono' => $data['Telefono'],
                'Especialidad' => $data['Especialidad'],
                'Carnet' => $Carnet,
                'IdInstructor' => $id,
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
                'SELECT IdUsuario FROM instructores
                 WHERE IdInstructor = :IdInstructor
                 FOR UPDATE'
            );
            $statement->execute(['IdInstructor' => $id]);
            $userId = $statement->fetchColumn();
            if ($userId === false) {
                $this->database->rollBack();
                return false;
            }

            $statement = $this->database->prepare(
                'SELECT COUNT(*) FROM cursos WHERE IdInstructor = :IdInstructor'
            );
            $statement->execute(['IdInstructor' => $id]);
            $courseCount = (int) $statement->fetchColumn();
            if ($courseCount > 0) {
                if ($replacementId === null || $replacementId === $id) {
                    throw new DomainException('Selecciona un instructor de reemplazo para reasignar sus cursos antes de eliminarlo.');
                }

                $this->reassignCourses($id, $replacementId);
            }

            $statement = $this->database->prepare(
                'DELETE FROM instructores WHERE IdInstructor = :IdInstructor'
            );
            $statement->execute(['IdInstructor' => $id]);
            $statement = $this->database->prepare(
                'DELETE FROM usuarios WHERE IdUsuario = :IdUsuario'
            );
            $statement->execute(['IdUsuario' => (int) $userId]);
            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /** Cambia el Estado de la cuenta y reasigna cursos si se desactiva. */
    public function setStatus(int $id, string $status, ?int $replacementId = null): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT i.IdUsuario
                 FROM instructores AS i
                 WHERE i.IdInstructor = :IdInstructor
                 FOR UPDATE'
            );
            $statement->execute(['IdInstructor' => $id]);
            $userId = $statement->fetchColumn();
            if ($userId === false) {
                $this->database->rollBack();
                return false;
            }

            if ($status === 'Inactivo') {
                $statement = $this->database->prepare(
                    'SELECT COUNT(*) FROM cursos WHERE IdInstructor = :IdInstructor'
                );
                $statement->execute(['IdInstructor' => $id]);
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
                 INNER JOIN instructores AS i ON i.IdUsuario = u.IdUsuario
                 SET u.Estado_Cuenta = :Estado_Cuenta
                 WHERE i.IdInstructor = :IdInstructor'
            );
            $statement->execute([
                'Estado_Cuenta' => $status,
                'IdInstructor' => $id,
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
     * Reasigna los cursos a un instructor distinto que esté activo.
     *
     * @throws DomainException Si el reemplazo es el mismo instructor o no está activo.
     */
    private function reassignCourses(int $instructorId, int $replacementId): void
    {
        if ($replacementId === $instructorId) {
            throw new DomainException('Selecciona otro instructor activo para reasignar los cursos.');
        }

        $statement = $this->database->prepare(
            "SELECT i.IdInstructor
             FROM instructores AS i
             INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
             WHERE i.IdInstructor = :IdInstructor
               AND u.Estado_Cuenta = 'Activo'
             FOR UPDATE"
        );
        $statement->execute(['IdInstructor' => $replacementId]);
        if ($statement->fetchColumn() === false) {
            throw new DomainException('El instructor de reemplazo debe existir y estar activo.');
        }

        $statement = $this->database->prepare(
            'UPDATE cursos
             SET IdInstructor = :replacement_id
             WHERE IdInstructor = :IdInstructor'
        );
        $statement->execute([
            'replacement_id' => $replacementId,
            'IdInstructor' => $instructorId,
        ]);
    }

    /** Comprueba si existe un perfil de instructor por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM instructores WHERE IdInstructor = :IdInstructor'
        );
        $statement->execute(['IdInstructor' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
