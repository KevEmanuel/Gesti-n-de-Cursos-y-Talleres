<?php

declare(strict_types=1);

/**
 * Gestiona inscripciones, cupos, cambios de estado y conflictos de horario.
 */
final class Solicitud extends BaseModel
{
    protected function tableName(): string
    {
        return 'inscripciones';
    }

    /**
     * Obtiene los registros del modulo para su consulta.
     */
    public function all(): array
    {
        return $this->database->query(
            'SELECT i.id_inscripcion, i.id_curso, i.id_estudiante, i.estado_inscripcion,
                    i.motivo_rechazo, i.fecha_inscripcion, e.carnet, u.nombres, u.apellidos, u.correo,
                    c.nombre_curso
             FROM inscripciones AS i
             INNER JOIN estudiantes AS e ON e.id_estudiante = i.id_estudiante
             INNER JOIN usuarios AS u ON u.id_usuario = e.id_usuario
             INNER JOIN cursos AS c ON c.id_curso = i.id_curso
             ORDER BY i.fecha_inscripcion DESC'
        )->fetchAll();
    }

    /** Obtiene los estudiantes activos para el formulario de inscripcion. */
    public function students(): array
    {
        return $this->database->query(
            "SELECT e.id_estudiante, e.carnet, u.nombres, u.apellidos
             FROM estudiantes AS e
             INNER JOIN usuarios AS u ON u.id_usuario = e.id_usuario
             WHERE u.estado_cuenta = 'Activo'
             ORDER BY u.apellidos, u.nombres"
        )->fetchAll();
    }

    /**
     * Obtiene los cursos validos para asociarlos al modulo.
     */
    public function courses(): array
    {
        return $this->database->query(
            "SELECT c.id_curso, c.nombre_curso, d.cupos_disponibles
             FROM cursos AS c
             INNER JOIN vw_cursos_disponibilidad AS d ON d.id_curso = c.id_curso
             WHERE c.estado = 'Activo'
               AND d.cupos_disponibles > 0
               AND EXISTS (
                   SELECT 1 FROM periodos_inscripcion AS p
                   WHERE p.id_curso = c.id_curso
                     AND CURRENT_DATE BETWEEN p.fecha_inicio AND p.fecha_cierre
               )
             ORDER BY c.nombre_curso"
        )->fetchAll();
    }

    /**
     * Obtiene las inscripciones del estudiante indicado.
     */
    public function forStudent(int $studentId): array
    {
        $statement = $this->database->prepare(
            'SELECT i.id_inscripcion, i.id_curso, i.fecha_inscripcion,
                    i.estado_inscripcion, i.motivo_rechazo, c.nombre_curso, c.modalidad,
                    c.duracion, c.costo_curso
             FROM inscripciones AS i
             INNER JOIN cursos AS c ON c.id_curso = i.id_curso
             WHERE i.id_estudiante = :id_estudiante
             ORDER BY i.fecha_inscripcion DESC'
        );
        $statement->execute(['id_estudiante' => $studentId]);

        return $statement->fetchAll();
    }

    /**
     * Busca una inscripcion activa del estudiante para el curso indicado.
     */
    public function activeEnrollmentForStudent(int $studentId, int $courseId): ?array
    {
        $statement = $this->database->prepare(
            "SELECT id_inscripcion, estado_inscripcion
             FROM inscripciones
             WHERE id_estudiante = :id_estudiante
               AND id_curso = :id_curso
               AND estado_inscripcion IN ('Confirmada', 'Pendiente')
             LIMIT 1"
        );
        $statement->execute([
            'id_estudiante' => $studentId,
            'id_curso' => $courseId,
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
                'SELECT maximo_cupos, estado FROM cursos WHERE id_curso = :id_curso FOR UPDATE'
            );
            $statement->execute(['id_curso' => $courseId]);
            $course = $statement->fetch();
            if (!is_array($course) || $course['estado'] !== 'Activo') {
                throw new DomainException('El curso no está disponible para nuevas solicitudes.');
            }
            $this->lockStudent($studentId);

            $statement = $this->database->prepare(
                "SELECT 1 FROM usuarios AS u
                 INNER JOIN estudiantes AS e ON e.id_usuario = u.id_usuario
                 WHERE e.id_estudiante = :id_estudiante AND u.estado_cuenta = 'Activo'"
            );
            $statement->execute(['id_estudiante' => $studentId]);
            if ($statement->fetchColumn() === false) {
                throw new DomainException('El estudiante no existe o su cuenta está inactiva.');
            }

            $statement = $this->database->prepare(
                'SELECT 1 FROM periodos_inscripcion
                 WHERE id_curso = :id_curso
                   AND CURRENT_DATE BETWEEN fecha_inicio AND fecha_cierre'
            );
            $statement->execute(['id_curso' => $courseId]);
            if ($statement->fetchColumn() === false) {
                throw new DomainException('No hay un período de inscripción vigente para este curso.');
            }

            $statement = $this->database->prepare(
                "SELECT COUNT(*) FROM inscripciones
                 WHERE id_estudiante = :id_estudiante
                   AND id_curso = :id_curso
                   AND estado_inscripcion IN ('Confirmada', 'Pendiente')"
            );
            $statement->execute([
                'id_estudiante' => $studentId,
                'id_curso' => $courseId,
            ]);
            if ((int) $statement->fetchColumn() > 0) {
                throw new DomainException('El estudiante ya tiene una inscripción activa en este curso.');
            }

            $statement = $this->database->prepare(
                "SELECT COUNT(*) FROM inscripciones
                 WHERE id_curso = :id_curso
                   AND estado_inscripcion IN ('Confirmada', 'Pendiente')"
            );
            $statement->execute(['id_curso' => $courseId]);
            $activeEnrollments = (int) $statement->fetchColumn();
            if ($activeEnrollments >= (int) $course['maximo_cupos']) {
                $this->setCourseFull($courseId);
                $this->database->commit();
                throw new DomainException('El curso alcanzó su capacidad máxima.');
            }

            if ($this->hasScheduleConflict($studentId, $courseId)) {
                throw new DomainException('El horario del curso se cruza con otra inscripción activa del estudiante.');
            }

            $statement = $this->database->prepare(
                "INSERT INTO inscripciones
                    (id_estudiante, id_curso, id_admin, estado_inscripcion)
                 VALUES
                    (:id_estudiante, :id_curso, :id_admin, 'Pendiente')"
            );
            $statement->execute([
                'id_estudiante' => $studentId,
                'id_curso' => $courseId,
                'id_admin' => $administratorId,
            ]);

            if ($activeEnrollments + 1 >= (int) $course['maximo_cupos']) {
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
                'SELECT id_curso
                 FROM inscripciones
                 WHERE id_inscripcion = :id_inscripcion
                   AND id_estudiante = :id_estudiante'
            );
            $statement->execute([
                'id_inscripcion' => $enrollmentId,
                'id_estudiante' => $studentId,
            ]);
            $courseId = $statement->fetchColumn();
            if ($courseId === false) {
                $this->database->rollBack();
                return false;
            }

            $this->lockCourse((int) $courseId);
            $statement = $this->database->prepare(
                'SELECT estado_inscripcion
                 FROM inscripciones
                 WHERE id_inscripcion = :id_inscripcion
                   AND id_estudiante = :id_estudiante
                 FOR UPDATE'
            );
            $statement->execute([
                'id_inscripcion' => $enrollmentId,
                'id_estudiante' => $studentId,
            ]);
            $status = $statement->fetchColumn();
            if (!in_array($status, ['Pendiente', 'Confirmada'], true)) {
                throw new DomainException('Solo puedes darte de baja de solicitudes pendientes o inscripciones activas.');
            }

            $statement = $this->database->prepare(
                "UPDATE inscripciones
                 SET estado_inscripcion = 'Cancelada'
                 WHERE id_inscripcion = :id_inscripcion"
            );
            $statement->execute(['id_inscripcion' => $enrollmentId]);
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
     * Cambia el estado de la solicitud dentro de una transaccion.
     */
    public function changeStatus(int $enrollmentId, string $newStatus, ?string $rejectionReason = null): bool
    {
        if (!in_array($newStatus, ['Pendiente', 'Confirmada', 'Cancelada', 'Rechazada'], true)) {
            throw new DomainException('El estado solicitado no es válido.');
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
        if (strlen($rejectionReason) > 1000) {
            throw new DomainException('El motivo de rechazo no puede superar 1000 caracteres.');
        }

        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT id_curso, id_estudiante, estado_inscripcion
                 FROM inscripciones WHERE id_inscripcion = :id_inscripcion'
            );
            $statement->execute(['id_inscripcion' => $enrollmentId]);
            $enrollment = $statement->fetch();
            if (!is_array($enrollment)) {
                $this->database->rollBack();
                return false;
            }

            $courseId = (int) $enrollment['id_curso'];
            $this->lockCourse($courseId);
            $oldStatus = $enrollment['estado_inscripcion'];

            if (in_array($newStatus, ['Confirmada', 'Pendiente'], true)
                && !in_array($oldStatus, ['Confirmada', 'Pendiente'], true)) {
                $statement = $this->database->prepare(
                    'SELECT maximo_cupos FROM cursos WHERE id_curso = :id_curso'
                );
                $statement->execute(['id_curso' => $courseId]);
                $capacity = (int) $statement->fetchColumn();

                $statement = $this->database->prepare(
                    "SELECT COUNT(*) FROM inscripciones
                     WHERE id_curso = :id_curso
                       AND estado_inscripcion IN ('Confirmada', 'Pendiente')"
                );
                $statement->execute(['id_curso' => $courseId]);
                if ((int) $statement->fetchColumn() >= $capacity) {
                    throw new DomainException('No se puede reactivar la inscripción: el curso está lleno.');
                }

                // Validar conflictos de horarios al reactivar una inscripción.
                // Evita que un estudiante tenga dos cursos en horas superpuestas.
                if ($this->hasScheduleConflict((int) $enrollment['id_estudiante'],$courseId)) {
                    throw new DomainException('No se puede reactivar la inscripción porque el estudiante tiene otro curso en ese horario.');
                }
            }

            if ($oldStatus !== $newStatus) {
                $statement = $this->database->prepare(
                    'UPDATE inscripciones
                     SET estado_inscripcion = :estado,
                         motivo_rechazo = :motivo_rechazo
                     WHERE id_inscripcion = :id_inscripcion'
                );
                $statement->execute([
                    'estado' => $newStatus,
                    'motivo_rechazo' => $newStatus === 'Rechazada' ? $rejectionReason : null,
                    'id_inscripcion' => $enrollmentId,
                ]);
            } elseif ($newStatus === 'Rechazada' && $oldStatus === $newStatus) {
                $statement = $this->database->prepare(
                    'UPDATE inscripciones
                     SET motivo_rechazo = :motivo_rechazo
                     WHERE id_inscripcion = :id_inscripcion'
                );
                $statement->execute([
                    'motivo_rechazo' => $rejectionReason,
                    'id_inscripcion' => $enrollmentId,
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

    private function hasScheduleConflict(int $studentId, int $courseId): bool
    {
        $statement = $this->database->prepare(
            "SELECT 1
             FROM inscripciones AS enrollment
             INNER JOIN horarios AS existing_schedule ON existing_schedule.id_curso = enrollment.id_curso
             INNER JOIN horarios AS requested_schedule ON requested_schedule.id_curso = :requested_course
                 AND requested_schedule.dia = existing_schedule.dia
                 AND requested_schedule.hora_inicio < existing_schedule.hora_finalizacion
                 AND requested_schedule.hora_finalizacion > existing_schedule.hora_inicio
             WHERE enrollment.id_estudiante = :id_estudiante
               AND enrollment.estado_inscripcion IN ('Confirmada', 'Pendiente')
             LIMIT 1"
        );
        $statement->execute([
            'requested_course' => $courseId,
            'id_estudiante' => $studentId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    private function lockCourse(int $courseId): void
    {
        $statement = $this->database->prepare(
            'SELECT id_curso FROM cursos WHERE id_curso = :id_curso FOR UPDATE'
        );
        $statement->execute(['id_curso' => $courseId]);
    }

    private function lockStudent(int $studentId): void
    {
        $statement = $this->database->prepare(
            'SELECT id_estudiante FROM estudiantes WHERE id_estudiante = :id_estudiante FOR UPDATE'
        );
        $statement->execute(['id_estudiante' => $studentId]);
    }

    private function setCourseFull(int $courseId): void
    {
        $statement = $this->database->prepare(
            "UPDATE cursos SET estado = 'Lleno'
             WHERE id_curso = :id_curso AND estado IN ('Activo', 'Lleno')"
        );
        $statement->execute(['id_curso' => $courseId]);
    }

    private function refreshCourseCapacityState(int $courseId): void
    {
        $statement = $this->database->prepare(
            "SELECT c.maximo_cupos, c.estado, COUNT(i.id_inscripcion) AS inscritos
             FROM cursos AS c
             LEFT JOIN inscripciones AS i
                ON i.id_curso = c.id_curso
                AND i.estado_inscripcion IN ('Confirmada', 'Pendiente')
             WHERE c.id_curso = :id_curso
             GROUP BY c.id_curso, c.maximo_cupos, c.estado"
        );
        $statement->execute(['id_curso' => $courseId]);
        $course = $statement->fetch();

        if (!is_array($course)) {
            return;
        }

        if ((int) $course['inscritos'] >= (int) $course['maximo_cupos']
            && in_array($course['estado'], ['Activo', 'Lleno'], true)) {
            $this->setCourseFull($courseId);
        } elseif ($course['estado'] === 'Lleno') {
            $statement = $this->database->prepare(
                "UPDATE cursos SET estado = 'Activo' WHERE id_curso = :id_curso"
            );
            $statement->execute(['id_curso' => $courseId]);
        }
    }
}
