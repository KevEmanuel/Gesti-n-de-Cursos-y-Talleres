<?php

declare(strict_types=1);

/**
 * Consulta y mantiene cursos, catalogo publico y datos relacionados.
 */
final class Curso extends BaseModel
{
    protected function tableName(): string
    {
        return 'cursos';
    }

    /**
     * Obtiene los registros del modulo para su consulta.
     */
    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT c.id_curso, c.id_categoria, c.id_instructor, c.nombre_curso, c.descripcion, c.duracion, c.modalidad,
                    c.requisitos, c.costo_curso, c.maximo_cupos, c.estado,
                    ca.nombre AS categoria, u.nombres, u.apellidos,
                    d.inscritos_activos, d.cupos_disponibles
             FROM cursos AS c
             INNER JOIN categorias AS ca ON ca.id_categoria = c.id_categoria
             INNER JOIN instructores AS i ON i.id_instructor = c.id_instructor
             INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
             LEFT JOIN vw_cursos_disponibilidad AS d ON d.id_curso = c.id_curso
             ORDER BY c.nombre_curso'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene los cursos publicados aplicando los filtros de busqueda.
     */
    public function publicCatalog(array $filters): array
    {
        $sql = "SELECT c.id_curso, c.nombre_curso, c.descripcion, c.duracion,
                       c.modalidad, c.requisitos, c.costo_curso, c.estado,
                       ca.nombre AS categoria, u.nombres AS instructor_nombres,
                       u.apellidos AS instructor_apellidos,
                       d.maximo_cupos, d.inscritos_activos, d.cupos_disponibles,
                       p.fecha_inicio AS periodo_inicio, p.fecha_cierre AS periodo_cierre
                FROM cursos AS c
                INNER JOIN categorias AS ca ON ca.id_categoria = c.id_categoria
                INNER JOIN instructores AS i ON i.id_instructor = c.id_instructor
                INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
                LEFT JOIN vw_cursos_disponibilidad AS d ON d.id_curso = c.id_curso
                LEFT JOIN periodos_inscripcion AS p
                    ON p.id_curso = c.id_curso
                    AND CURRENT_DATE BETWEEN p.fecha_inicio AND p.fecha_cierre
                WHERE c.estado IN ('Activo', 'Lleno')
                  AND ca.estado = 'Activo'
                  AND u.estado_cuenta = 'Activo'";
        $parameters = [];

        if ($filters['id_categoria'] !== '') {
            $sql .= ' AND c.id_categoria = :id_categoria';
            $parameters['id_categoria'] = (int) $filters['id_categoria'];
        }
        if ($filters['modalidad'] !== '') {
            $sql .= ' AND c.modalidad = :modalidad';
            $parameters['modalidad'] = $filters['modalidad'];
        }
        if ($filters['busqueda'] !== '') {
            $sql .= ' AND (c.nombre_curso LIKE :busqueda_nombre
                          OR c.descripcion LIKE :busqueda_descripcion
                          OR ca.nombre LIKE :busqueda_categoria
                          OR u.nombres LIKE :busqueda_instructor
                          OR u.apellidos LIKE :busqueda_apellido)';
            $term = '%' . $filters['busqueda'] . '%';
            $parameters['busqueda_nombre'] = $term;
            $parameters['busqueda_descripcion'] = $term;
            $parameters['busqueda_categoria'] = $term;
            $parameters['busqueda_instructor'] = $term;
            $parameters['busqueda_apellido'] = $term;
        }

        $sql .= ' ORDER BY c.estado = \'Activo\' DESC, c.nombre_curso';
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
            "SELECT c.id_curso, c.nombre_curso, c.descripcion, c.duracion,
                    c.modalidad, c.requisitos, c.costo_curso, c.estado,
                    ca.nombre AS categoria, u.nombres AS instructor_nombres,
                    u.apellidos AS instructor_apellidos,
                    d.maximo_cupos, d.inscritos_activos, d.cupos_disponibles,
                    p.fecha_inicio AS periodo_inicio, p.fecha_cierre AS periodo_cierre
             FROM cursos AS c
             INNER JOIN categorias AS ca ON ca.id_categoria = c.id_categoria
             INNER JOIN instructores AS i ON i.id_instructor = c.id_instructor
             INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
             LEFT JOIN vw_cursos_disponibilidad AS d ON d.id_curso = c.id_curso
             LEFT JOIN periodos_inscripcion AS p
                ON p.id_curso = c.id_curso
                AND CURRENT_DATE BETWEEN p.fecha_inicio AND p.fecha_cierre
             WHERE c.id_curso = :id_curso
               AND c.estado IN ('Activo', 'Lleno')
               AND ca.estado = 'Activo'
               AND u.estado_cuenta = 'Activo'
             LIMIT 1"
        );
        $statement->execute(['id_curso' => $courseId]);
        $course = $statement->fetch();

        return is_array($course) ? $course : null;
    }

    /**
     * Obtiene los horarios publicados de un curso.
     */
    public function publicSchedule(int $courseId): array
    {
        $statement = $this->database->prepare(
            'SELECT dia, hora_inicio, hora_finalizacion
             FROM horarios
             WHERE id_curso = :id_curso
             ORDER BY FIELD(dia, \'Lunes\', \'Martes\', \'Miercoles\', \'Jueves\', \'Viernes\', \'Sabado\', \'Domingo\'), hora_inicio'
        );
        $statement->execute(['id_curso' => $courseId]);

        return $statement->fetchAll();
    }

    /**
     * Obtiene las categorias disponibles para filtrar los reportes.
     */
    public function categories(): array
    {
        return $this->database->query(
            "SELECT id_categoria, nombre FROM categorias WHERE estado = 'Activo' ORDER BY nombre"
        )->fetchAll();
    }

    /**
     * Obtiene los instructores activos disponibles para asignacion.
     */
    public function instructors(): array
    {
        return $this->database->query(
            "SELECT i.id_instructor, u.nombres, u.apellidos
             FROM instructores AS i
             INNER JOIN usuarios AS u ON u.id_usuario = i.id_usuario
             WHERE u.estado_cuenta = 'Activo'
             ORDER BY u.apellidos, u.nombres"
        )->fetchAll();
    }

    /**
     * Valida y registra un nuevo elemento del modulo.
     */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO cursos
                (id_categoria, id_instructor, id_admin, nombre_curso, descripcion,
                 duracion, modalidad, requisitos, costo_curso, maximo_cupos, estado)
             VALUES
                (:id_categoria, :id_instructor, :id_admin, :nombre_curso, :descripcion,
                 :duracion, :modalidad, :requisitos, :costo_curso, :maximo_cupos, :estado)'
        );
        $statement->execute($data);
    }

    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
    public function update(int $id, array $data): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT id_curso FROM cursos WHERE id_curso = :id_curso FOR UPDATE'
            );
            $statement->execute(['id_curso' => $id]);
            if ($statement->fetchColumn() === false) {
                $this->database->rollBack();
                return false;
            }

            $statement = $this->database->prepare(
                "SELECT COUNT(*) FROM inscripciones
                 WHERE id_curso = :id_curso
                   AND estado_inscripcion IN ('Confirmada', 'Pendiente')"
            );
            $statement->execute(['id_curso' => $id]);
            $activeEnrollments = (int) $statement->fetchColumn();

            if ($data['maximo_cupos'] < $activeEnrollments) {
                $this->database->rollBack();
                throw new DomainException(
                    'La capacidad no puede ser menor que las inscripciones activas (' . $activeEnrollments . ').'
                );
            }

            $statement = $this->database->prepare(
                'UPDATE cursos
                 SET id_categoria = :id_categoria, id_instructor = :id_instructor,
                     nombre_curso = :nombre_curso, descripcion = :descripcion,
                     duracion = :duracion, modalidad = :modalidad, requisitos = :requisitos,
                     costo_curso = :costo_curso, maximo_cupos = :maximo_cupos, estado = :estado
                 WHERE id_curso = :id_curso'
            );
            $data['id_curso'] = $id;
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

    /**
     * Actualiza el estado del registro y aplica las reglas del modulo.
     */
    public function setStatus(int $id, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE cursos SET estado = :estado WHERE id_curso = :id_curso'
        );
        $statement->execute(['estado' => $status, 'id_curso' => $id]);

        return $this->exists($id);
    }

    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM cursos WHERE id_curso = :id_curso'
        );
        $statement->execute(['id_curso' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
