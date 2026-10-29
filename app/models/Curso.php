<?php

declare(strict_types=1);

/**
 * Consulta y mantiene cursos, catalogo publico y datos relacionados.
 */
final class Curso extends BaseModel
{
    /** Obtiene cursos administrativos junto con instructor y datos de disponibilidad. */
    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT c.IdCurso, c.IdCategoria, c.IdInstructor, c.Nombre_Curso, c.Descripcion, c.Duracion, c.Modalidad,
                    c.Requisitos, c.Costo_curso, c.Maximo_cupos, c.Estado,
                    ca.Nombre AS categoria, u.Nombres, u.Apellidos,
                    d.InscritosActivos, d.Cupos_Disponibles
             FROM cursos AS c
             INNER JOIN categorias AS ca ON ca.IdCategoria = c.IdCategoria
             INNER JOIN instructores AS i ON i.IdInstructor = c.IdInstructor
             INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
             LEFT JOIN vw_cursos_disponibilidad AS d ON d.IdCurso = c.IdCurso
             ORDER BY c.Nombre_Curso'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene los cursos publicados aplicando los filtros de busqueda.
     */
    public function publicCatalog(array $filters): array
    {
        $sql = "SELECT c.IdCurso, c.Nombre_Curso, c.Descripcion, c.Duracion,
                       c.Modalidad, c.Requisitos, c.Costo_curso, c.Estado,
                       ca.Nombre AS categoria, u.Nombres AS instructor_nombres,
                       u.Apellidos AS instructor_apellidos,
                       d.Maximo_cupos, d.InscritosActivos, d.Cupos_Disponibles,
                       p.FechaInicio AS periodo_inicio, p.FechaCierre AS periodo_cierre
                FROM cursos AS c
                INNER JOIN categorias AS ca ON ca.IdCategoria = c.IdCategoria
                INNER JOIN instructores AS i ON i.IdInstructor = c.IdInstructor
                INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
                LEFT JOIN vw_cursos_disponibilidad AS d ON d.IdCurso = c.IdCurso
                LEFT JOIN periodos_inscripcion AS p
                    ON p.IdCurso = c.IdCurso
                    AND CURRENT_DATE BETWEEN p.FechaInicio AND p.FechaCierre
                WHERE c.Estado IN ('Activo', 'Lleno')
                  AND ca.Estado = 'Activo'
                  AND u.Estado_Cuenta = 'Activo'";
        $parameters = [];

        if ($filters['IdCategoria'] !== '') {
            $sql .= ' AND c.IdCategoria = :IdCategoria';
            $parameters['IdCategoria'] = (int) $filters['IdCategoria'];
        }
        if ($filters['Modalidad'] !== '') {
            $sql .= ' AND c.Modalidad = :Modalidad';
            $parameters['Modalidad'] = $filters['Modalidad'];
        }
        if ($filters['busqueda'] !== '') {
            $sql .= ' AND (c.Nombre_Curso LIKE :busqueda_nombre
                          OR c.Descripcion LIKE :busqueda_descripcion
                          OR ca.Nombre LIKE :busqueda_categoria
                          OR u.Nombres LIKE :busqueda_instructor
                          OR u.Apellidos LIKE :busqueda_apellido)';
            $term = '%' . $filters['busqueda'] . '%';
            $parameters['busqueda_nombre'] = $term;
            $parameters['busqueda_descripcion'] = $term;
            $parameters['busqueda_categoria'] = $term;
            $parameters['busqueda_instructor'] = $term;
            $parameters['busqueda_apellido'] = $term;
        }

        $sql .= ' ORDER BY c.Estado = \'Activo\' DESC, c.Nombre_Curso';
        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * Obtiene los datos de un curso visible en el catalogo publico.
     */
    public function publicCourse(int $courseId): ?array
    {
        $statement = $this->database->prepare(
            "SELECT c.IdCurso, c.Nombre_Curso, c.Descripcion, c.Duracion,
                    c.Modalidad, c.Requisitos, c.Costo_curso, c.Estado,
                    ca.Nombre AS categoria, u.Nombres AS instructor_nombres,
                    u.Apellidos AS instructor_apellidos,
                    d.Maximo_cupos, d.InscritosActivos, d.Cupos_Disponibles,
                    p.FechaInicio AS periodo_inicio, p.FechaCierre AS periodo_cierre
             FROM cursos AS c
             INNER JOIN categorias AS ca ON ca.IdCategoria = c.IdCategoria
             INNER JOIN instructores AS i ON i.IdInstructor = c.IdInstructor
             INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
             LEFT JOIN vw_cursos_disponibilidad AS d ON d.IdCurso = c.IdCurso
             LEFT JOIN periodos_inscripcion AS p
                ON p.IdCurso = c.IdCurso
                AND CURRENT_DATE BETWEEN p.FechaInicio AND p.FechaCierre
             WHERE c.IdCurso = :IdCurso
               AND c.Estado IN ('Activo', 'Lleno')
               AND ca.Estado = 'Activo'
               AND u.Estado_Cuenta = 'Activo'
             LIMIT 1"
        );
        $statement->execute(['IdCurso' => $courseId]);
        $course = $statement->fetch();

        return is_array($course) ? $course : null;
    }

    /**
     * Obtiene los horarios publicados de un curso.
     */
    public function publicSchedule(int $courseId): array
    {
        $statement = $this->database->prepare(
            'SELECT Dia, HoraInicio, Hora_finalizacion
             FROM horarios
             WHERE IdCurso = :IdCurso
             ORDER BY FIELD(Dia, \'Lunes\', \'Martes\', \'Miercoles\', \'Jueves\', \'Viernes\', \'Sabado\', \'Domingo\'), HoraInicio'
        );
        $statement->execute(['IdCurso' => $courseId]);

        return $statement->fetchAll();
    }

    /**
     * Obtiene las categorias disponibles para filtrar los reportes.
     */
    public function categories(): array
    {
        return $this->database->query(
            "SELECT IdCategoria, Nombre FROM categorias WHERE Estado = 'Activo' ORDER BY Nombre"
        )->fetchAll();
    }

    /**
     * Obtiene los instructores activos disponibles para asignacion.
     */
    public function instructors(): array
    {
        return $this->database->query(
            "SELECT i.IdInstructor, u.Nombres, u.Apellidos
             FROM instructores AS i
             INNER JOIN usuarios AS u ON u.IdUsuario = i.IdUsuario
             WHERE u.Estado_Cuenta = 'Activo'
             ORDER BY u.Apellidos, u.Nombres"
        )->fetchAll();
    }

    /** Crea un curso con su categoría, instructor, administrador y capacidad inicial. */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO cursos
                (IdCategoria, IdInstructor, IdAdmin, Nombre_Curso, Descripcion,
                 Duracion, Modalidad, Requisitos, Costo_curso, Maximo_cupos, Estado)
             VALUES
                (:IdCategoria, :IdInstructor, :IdAdmin, :Nombre_Curso, :Descripcion,
                 :Duracion, :Modalidad, :Requisitos, :Costo_curso, :Maximo_cupos, :Estado)'
        );
        $statement->execute($data);
    }

    /** Actualiza los datos y Estado del curso dentro de una transacción. */
    public function update(int $id, array $data): bool
    {
        $this->database->beginTransaction();

        try {
            // Obtener el instructor actual antes de modificar el curso.
            $statement = $this->database->prepare('SELECT IdInstructor FROM cursos WHERE IdCurso = :IdCurso FOR UPDATE');
            $statement->execute(['IdCurso' => $id]);

            $currentInstructor = $statement->fetchColumn();

            if ($currentInstructor === false) {
                $this->database->rollBack();
                return false;
            }

            $statement = $this->database->prepare(
                "SELECT COUNT(*) FROM inscripciones
                 WHERE IdCurso = :IdCurso
                   AND EstadoInscripcion IN ('Confirmada', 'Pendiente')"
            );
            $statement->execute(['IdCurso' => $id]);
            $activeEnrollments = (int) $statement->fetchColumn();

            if ($data['Maximo_cupos'] < $activeEnrollments) {
                $this->database->rollBack();
                throw new DomainException(
                    'La capacidad no puede ser menor que las inscripciones activas (' . $activeEnrollments . ').'
                );
            }
            // Validar conflictos de horario al cambiar el instructor de un curso.
            // Impide asignar un profesor que ya imparte otra clase en el mismo horario.
            if ((int) $currentInstructor !== (int) $data['IdInstructor']) {

            $statement = $this->database->prepare(
        'SELECT 1
         FROM horarios AS actual
         INNER JOIN horarios AS otro
             ON otro.Dia = actual.Dia
            AND actual.HoraInicio < otro.Hora_finalizacion
            AND actual.Hora_finalizacion > otro.HoraInicio
         INNER JOIN cursos AS curso_otro
             ON curso_otro.IdCurso = otro.IdCurso
         WHERE actual.IdCurso = :IdCurso
           AND otro.IdCurso <> :id_curso_diferente
           AND curso_otro.IdInstructor = :IdInstructor
         LIMIT 1'
    );

    $statement->execute([
        'IdCurso' => $id,
        'id_curso_diferente' => $id,
        'IdInstructor' => $data['IdInstructor'],
    ]);

    if ($statement->fetchColumn() !== false) {
        throw new DomainException(
            'No se puede asignar este instructor porque ya tiene una clase en ese horario.'
        );
    }
}

            $statement = $this->database->prepare(
                'UPDATE cursos
                 SET IdCategoria = :IdCategoria, IdInstructor = :IdInstructor,
                     Nombre_Curso = :Nombre_Curso, Descripcion = :Descripcion,
                     Duracion = :Duracion, Modalidad = :Modalidad, Requisitos = :Requisitos,
                     Costo_curso = :Costo_curso, Maximo_cupos = :Maximo_cupos, Estado = :Estado
                 WHERE IdCurso = :IdCurso'
            );
            $data['IdCurso'] = $id;
            $statement->execute($data);
            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /** Cambia el Estado del curso y comprueba que exista el registro. */
    public function setStatus(int $id, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE cursos SET Estado = :Estado WHERE IdCurso = :IdCurso'
        );
        $statement->execute(['Estado' => $status, 'IdCurso' => $id]);

        return $this->exists($id);
    }

    /** Comprueba si existe un curso por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM cursos WHERE IdCurso = :IdCurso'
        );
        $statement->execute(['IdCurso' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
