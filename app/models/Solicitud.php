<?php

declare(strict_types=1);

/**
 * Gestiona inscripciones, cupos, cambios de Estado y conflictos de horario.
 */
final class Solicitud extends BaseModel
{
    /** Obtiene las inscripciones con datos del estudiante y del curso asociado. */
    public function all(): array
    {
        return $this->database->query(
            'SELECT i.IdInscripcion, i.IdCurso, i.IdEstudiante, i.EstadoInscripcion,
                    i.MotivoRechazo, i.FechaInscripcion, e.Carnet, u.Nombres, u.Apellidos, u.Correo,
                    c.Nombre_Curso
             FROM inscripciones AS i
             INNER JOIN estudiantes AS e ON e.IdEstudiante = i.IdEstudiante
             INNER JOIN usuarios AS u ON u.IdUsuario = e.IdUsuario
             INNER JOIN cursos AS c ON c.IdCurso = i.IdCurso
             ORDER BY i.FechaInscripcion DESC'
        )->fetchAll();
    }

    /** Obtiene los estudiantes activos disponibles para el formulario de inscripción. */
    public function students(): array
    {
        return $this->database->query(
            "SELECT e.IdEstudiante, e.Carnet, u.Nombres, u.Apellidos
             FROM estudiantes AS e
             INNER JOIN usuarios AS u ON u.IdUsuario = e.IdUsuario
             WHERE u.Estado_Cuenta = 'Activo'
             ORDER BY u.Apellidos, u.Nombres"
        )->fetchAll();
    }

    /**
     * Obtiene los cursos validos para asociarlos al modulo.
     */
    public function courses(): array
    {
        return $this->database->query(
            "SELECT c.IdCurso, c.Nombre_Curso, d.Cupos_Disponibles
             FROM cursos AS c
             INNER JOIN vw_cursos_disponibilidad AS d ON d.IdCurso = c.IdCurso
             WHERE c.Estado = 'Activo'
               AND d.Cupos_Disponibles > 0
               AND EXISTS (
                   SELECT 1 FROM periodos_inscripcion AS p
                   WHERE p.IdCurso = c.IdCurso
                     AND CURRENT_DATE BETWEEN p.FechaInicio AND p.FechaCierre
               )
             ORDER BY c.Nombre_Curso"
        )->fetchAll();
    }

    /**
     * Obtiene las inscripciones del estudiante indicado.
     */
    public function forStudent(int $studentId): array
    {
        $statement = $this->database->prepare(
            'SELECT i.IdInscripcion, i.IdCurso, i.FechaInscripcion,
                    i.EstadoInscripcion, i.MotivoRechazo, c.Nombre_Curso, c.Modalidad,
                    c.Duracion, c.Costo_curso
             FROM inscripciones AS i
             INNER JOIN cursos AS c ON c.IdCurso = i.IdCurso
             WHERE i.IdEstudiante = :IdEstudiante
             ORDER BY i.FechaInscripcion DESC'
        );
        $statement->execute(['IdEstudiante' => $studentId]);

        return $statement->fetchAll();
    }

    /**
     * Busca una inscripcion activa del estudiante para el curso indicado.
     */
    public function activeEnrollmentForStudent(int $studentId, int $courseId): ?array
    {
        $statement = $this->database->prepare(
            "SELECT IdInscripcion, EstadoInscripcion
             FROM inscripciones
             WHERE IdEstudiante = :IdEstudiante
               AND IdCurso = :IdCurso
               AND EstadoInscripcion IN ('Confirmada', 'Pendiente')
             LIMIT 1"
        );
        $statement->execute([
            'IdEstudiante' => $studentId,
            'IdCurso' => $courseId,
        ]);
        $enrollment = $statement->fetch();

        return is_array($enrollment) ? $enrollment : null;
    }

    /**
     * Registra una inscripcion pendiente y verifica cupos y horarios.
     */
    public function createPending(int $studentId, int $courseId, ?int $administratorId = null): void
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT Maximo_cupos, Estado FROM cursos WHERE IdCurso = :IdCurso FOR UPDATE'
            );
            $statement->execute(['IdCurso' => $courseId]);
            $course = $statement->fetch();
            if (!is_array($course) || $course['Estado'] !== 'Activo') {
                throw new DomainException('El curso no está disponible para nuevas solicitudes.');
            }
            $this->lockStudent($studentId);

            $statement = $this->database->prepare(
                "SELECT 1 FROM usuarios AS u
                 INNER JOIN estudiantes AS e ON e.IdUsuario = u.IdUsuario
                 WHERE e.IdEstudiante = :IdEstudiante AND u.Estado_Cuenta = 'Activo'"
            );
            $statement->execute(['IdEstudiante' => $studentId]);
            if ($statement->fetchColumn() === false) {
                throw new DomainException('El estudiante no existe o su cuenta está inactiva.');
            }

            $statement = $this->database->prepare(
                'SELECT 1 FROM periodos_inscripcion
                 WHERE IdCurso = :IdCurso
                   AND CURRENT_DATE BETWEEN FechaInicio AND FechaCierre'
            );
            $statement->execute(['IdCurso' => $courseId]);
            if ($statement->fetchColumn() === false) {
                throw new DomainException('No hay un período de inscripción vigente para este curso.');
            }

            $statement = $this->database->prepare(
                "SELECT COUNT(*) FROM inscripciones
                 WHERE IdEstudiante = :IdEstudiante
                   AND IdCurso = :IdCurso
                   AND EstadoInscripcion IN ('Confirmada', 'Pendiente')"
            );
            $statement->execute([
                'IdEstudiante' => $studentId,
                'IdCurso' => $courseId,
            ]);
            if ((int) $statement->fetchColumn() > 0) {
                throw new DomainException('El estudiante ya tiene una inscripción activa en este curso.');
            }

            $statement = $this->database->prepare(
                "SELECT COUNT(*) FROM inscripciones
                 WHERE IdCurso = :IdCurso
                   AND EstadoInscripcion IN ('Confirmada', 'Pendiente')"
            );
            $statement->execute(['IdCurso' => $courseId]);
            $activeEnrollments = (int) $statement->fetchColumn();
            if ($activeEnrollments >= (int) $course['Maximo_cupos']) {
                $this->setCourseFull($courseId);
                $this->database->commit();
                throw new DomainException('El curso alcanzó su capacidad máxima.');
            }

            if ($this->hasScheduleConflict($studentId, $courseId)) {
                throw new DomainException('El horario del curso se cruza con otra inscripción activa del estudiante.');
            }

            $statement = $this->database->prepare(
                "INSERT INTO inscripciones
                    (IdEstudiante, IdCurso, IdAdmin, EstadoInscripcion)
                 VALUES
                    (:IdEstudiante, :IdCurso, :IdAdmin, 'Pendiente')"
            );
            $statement->execute([
                'IdEstudiante' => $studentId,
                'IdCurso' => $courseId,
                'IdAdmin' => $administratorId,
            ]);

            if ($activeEnrollments + 1 >= (int) $course['Maximo_cupos']) {
                $this->setCourseFull($courseId);
            }
            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Cancela la inscripcion activa del estudiante indicado.
     */
    public function cancelActiveForStudent(int $enrollmentId, int $studentId): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT IdCurso
                 FROM inscripciones
                 WHERE IdInscripcion = :IdInscripcion
                   AND IdEstudiante = :IdEstudiante'
            );
            $statement->execute([
                'IdInscripcion' => $enrollmentId,
                'IdEstudiante' => $studentId,
            ]);
            $courseId = $statement->fetchColumn();
            if ($courseId === false) {
                $this->database->rollBack();
                return false;
            }

            $this->lockCourse((int) $courseId);
            $statement = $this->database->prepare(
                'SELECT EstadoInscripcion
                 FROM inscripciones
                 WHERE IdInscripcion = :IdInscripcion
                   AND IdEstudiante = :IdEstudiante
                 FOR UPDATE'
            );
            $statement->execute([
                'IdInscripcion' => $enrollmentId,
                'IdEstudiante' => $studentId,
            ]);
            $status = $statement->fetchColumn();
            if (!in_array($status, ['Pendiente', 'Confirmada'], true)) {
                throw new DomainException('Solo puedes darte de baja de solicitudes pendientes o inscripciones activas.');
            }

            $statement = $this->database->prepare(
                "UPDATE inscripciones
                 SET EstadoInscripcion = 'Cancelada'
                 WHERE IdInscripcion = :IdInscripcion"
            );
            $statement->execute(['IdInscripcion' => $enrollmentId]);
            $this->refreshCourseCapacityState((int) $courseId);
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
     * Cambia el Estado de la solicitud dentro de una transaccion.
     */
    public function changeStatus(int $enrollmentId, string $newStatus, ?string $rejectionReason = null): bool
    {
        if (!in_array($newStatus, ['Pendiente', 'Confirmada', 'Cancelada', 'Rechazada'], true)) {
            throw new DomainException('El Estado solicitado no es válido.');
        }
        $rejectionReason = trim((string) $rejectionReason);
        if ($newStatus === 'Rechazada' && $rejectionReason === '') {
            throw new DomainException('Describe el motivo por el que se rechaza la solicitud.');
        }
        // Validar que el motivo de rechazo contenga al menos una letra.
        // Evita guardar únicamente números o símbolos.
        if ($newStatus === 'Rechazada' && preg_match('/\p{L}/u', $rejectionReason) !== 1) {
            throw new DomainException('El motivo de rechazo debe contener texto válido.');
        }
        if (mb_strlen($rejectionReason, 'UTF-8') > 1000) {
            throw new DomainException('El motivo de rechazo no puede superar 1000 caracteres.');
        }

        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT IdCurso, IdEstudiante, EstadoInscripcion
                 FROM inscripciones WHERE IdInscripcion = :IdInscripcion'
            );
            $statement->execute(['IdInscripcion' => $enrollmentId]);
            $enrollment = $statement->fetch();
            if (!is_array($enrollment)) {
                $this->database->rollBack();
                return false;
            }

            $courseId = (int) $enrollment['IdCurso'];
            $this->lockCourse($courseId);
            $oldStatus = $enrollment['EstadoInscripcion'];

            if (
                in_array($newStatus, ['Confirmada', 'Pendiente'], true)
                && !in_array($oldStatus, ['Confirmada', 'Pendiente'], true)
            ) {
                $statement = $this->database->prepare(
                    'SELECT Maximo_cupos FROM cursos WHERE IdCurso = :IdCurso'
                );
                $statement->execute(['IdCurso' => $courseId]);
                $capacity = (int) $statement->fetchColumn();

                $statement = $this->database->prepare(
                    "SELECT COUNT(*) FROM inscripciones
                     WHERE IdCurso = :IdCurso
                       AND EstadoInscripcion IN ('Confirmada', 'Pendiente')"
                );
                $statement->execute(['IdCurso' => $courseId]);
                if ((int) $statement->fetchColumn() >= $capacity) {
                    throw new DomainException('No se puede reactivar la inscripción: el curso está lleno.');
                }

                // Validar conflictos de horarios al reactivar una inscripción.
                // Evita que un estudiante tenga dos cursos en horas superpuestas.
                if ($this->hasScheduleConflict((int) $enrollment['IdEstudiante'], $courseId)) {
                    throw new DomainException('No se puede reactivar la inscripción porque el estudiante tiene otro curso en ese horario.');
                }
            }

            if ($oldStatus !== $newStatus) {
                $statement = $this->database->prepare(
                    'UPDATE inscripciones
                     SET EstadoInscripcion = :Estado,
                         MotivoRechazo = :MotivoRechazo
                     WHERE IdInscripcion = :IdInscripcion'
                );
                $statement->execute([
                    'Estado' => $newStatus,
                    'MotivoRechazo' => $newStatus === 'Rechazada' ? $rejectionReason : null,
                    'IdInscripcion' => $enrollmentId,
                ]);
            } elseif ($newStatus === 'Rechazada' && $oldStatus === $newStatus) {
                $statement = $this->database->prepare(
                    'UPDATE inscripciones
                     SET MotivoRechazo = :MotivoRechazo
                     WHERE IdInscripcion = :IdInscripcion'
                );
                $statement->execute([
                    'MotivoRechazo' => $rejectionReason,
                    'IdInscripcion' => $enrollmentId,
                ]);
            }

            $this->refreshCourseCapacityState($courseId);
            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /** Detecta solapamientos de horario con inscripciones activas del estudiante. */
    private function hasScheduleConflict(int $studentId, int $courseId): bool
    {
        $statement = $this->database->prepare(
            "SELECT 1
             FROM inscripciones AS enrollment
             INNER JOIN horarios AS existing_schedule ON existing_schedule.IdCurso = enrollment.IdCurso
             INNER JOIN horarios AS requested_schedule ON requested_schedule.IdCurso = :requested_course
                 AND requested_schedule.Dia = existing_schedule.Dia
                 AND requested_schedule.HoraInicio < existing_schedule.Hora_finalizacion
                 AND requested_schedule.Hora_finalizacion > existing_schedule.HoraInicio
             WHERE enrollment.IdEstudiante = :IdEstudiante
               AND enrollment.EstadoInscripcion IN ('Confirmada', 'Pendiente')
             LIMIT 1"
        );
        $statement->execute([
            'requested_course' => $courseId,
            'IdEstudiante' => $studentId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    /** Bloquea la fila del curso durante una transacción de inscripción. */
    private function lockCourse(int $courseId): void
    {
        $statement = $this->database->prepare(
            'SELECT IdCurso FROM cursos WHERE IdCurso = :IdCurso FOR UPDATE'
        );
        $statement->execute(['IdCurso' => $courseId]);
    }

    /** Bloquea la fila del estudiante durante una transacción de inscripción. */
    private function lockStudent(int $studentId): void
    {
        $statement = $this->database->prepare(
            'SELECT IdEstudiante FROM estudiantes WHERE IdEstudiante = :IdEstudiante FOR UPDATE'
        );
        $statement->execute(['IdEstudiante' => $studentId]);
    }

    /** Marca como lleno un curso activo que alcanzó su capacidad máxima. */
    private function setCourseFull(int $courseId): void
    {
        $statement = $this->database->prepare(
            "UPDATE cursos SET Estado = 'Lleno'
             WHERE IdCurso = :IdCurso AND Estado IN ('Activo', 'Lleno')"
        );
        $statement->execute(['IdCurso' => $courseId]);
    }

    /** Sincroniza el Estado del curso con el total de inscripciones activas. */
    private function refreshCourseCapacityState(int $courseId): void
    {
        $statement = $this->database->prepare(
            "SELECT c.Maximo_cupos, c.Estado, COUNT(i.IdInscripcion) AS inscritos
             FROM cursos AS c
             LEFT JOIN inscripciones AS i
                ON i.IdCurso = c.IdCurso
                AND i.EstadoInscripcion IN ('Confirmada', 'Pendiente')
             WHERE c.IdCurso = :IdCurso
             GROUP BY c.IdCurso, c.Maximo_cupos, c.Estado"
        );
        $statement->execute(['IdCurso' => $courseId]);
        $course = $statement->fetch();

        if (!is_array($course)) {
            return;
        }

        if (
            (int) $course['inscritos'] >= (int) $course['Maximo_cupos']
            && in_array($course['Estado'], ['Activo', 'Lleno'], true)
        ) {
            $this->setCourseFull($courseId);
        } elseif ($course['Estado'] === 'Lleno') {
            $statement = $this->database->prepare(
                "UPDATE cursos SET Estado = 'Activo' WHERE IdCurso = :IdCurso"
            );
            $statement->execute(['IdCurso' => $courseId]);
        }
    }
}
