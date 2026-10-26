<?php

declare(strict_types=1);

/**
 * Consulta y mantiene las cuentas y los perfiles de estudiantes.
 */
final class Estudiante extends BaseModel
{
    /** Obtiene los perfiles estudiantiles junto con los datos de sus cuentas. */
    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT e.id_estudiante, e.carnet, u.id_usuario, u.correo, u.nombres,
                    u.apellidos, u.telefono, u.edad, u.genero, u.estado_cuenta
             FROM estudiantes AS e
             INNER JOIN usuarios AS u ON u.id_usuario = e.id_usuario
             ORDER BY u.apellidos, u.nombres'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene los datos del perfil asociados a la cuenta estudiantil.
     */
    public function findForAccount(int $studentId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT e.id_estudiante, e.carnet, u.correo, u.nombres, u.apellidos,
                    u.telefono, u.edad, u.genero
             FROM estudiantes AS e
             INNER JOIN usuarios AS u ON u.id_usuario = e.id_usuario
             WHERE e.id_estudiante = :id_estudiante
               AND u.estado_cuenta = \'Activo\''
        );
        $statement->execute(['id_estudiante' => $studentId]);
        $student = $statement->fetch();

        return is_array($student) ? $student : null;
    }

    /**
     * Guarda los cambios permitidos en el perfil del estudiante.
     */
    public function updateOwnProfile(int $studentId, array $data): bool
    {
        $statement = $this->database->prepare(
            'UPDATE usuarios AS u
             INNER JOIN estudiantes AS e ON e.id_usuario = u.id_usuario
             SET u.telefono = :telefono, u.edad = :edad, u.genero = :genero
             WHERE e.id_estudiante = :id_estudiante
               AND u.estado_cuenta = \'Activo\''
        );
        $statement->execute([
            'telefono' => $data['telefono'],
            'edad' => $data['edad'],
            'genero' => $data['genero'],
            'id_estudiante' => $studentId,
        ]);

        return $this->findForAccount($studentId) !== null;
    }

    /** Crea la cuenta y el perfil estudiantil, generando su carnet en una transacción. */
    public function create(array $data): void
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'INSERT INTO usuarios
                    (correo, password_hash, nombres, apellidos, telefono, edad, genero)
                 VALUES
                    (:correo, :password_hash, :nombres, :apellidos, :telefono, :edad, :genero)'
            );
            $statement->execute([
                'correo' => $data['correo'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'],
                'edad' => $data['edad'],
                'genero' => $data['genero'],
            ]);

            $statement = $this->database->prepare(
                'INSERT INTO estudiantes (id_usuario, carnet)
                 VALUES (:id_usuario, :carnet)'
            );
            $statement->execute([
                'id_usuario' => (int) $this->database->lastInsertId(),
                'carnet' => Identificador::generarCarnet(
                    $this->database,
                    (string) $data['apellidos'],
                    'estudiantes'
                ),
            ]);

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /** Actualiza los datos de cuenta y perfil y regenera el carnet si cambian apellidos. */
    public function update(int $id, array $data): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT e.carnet, u.apellidos
                 FROM estudiantes AS e
                 INNER JOIN usuarios AS u ON u.id_usuario = e.id_usuario
                 WHERE e.id_estudiante = :id_estudiante
                 FOR UPDATE'
            );
            $statement->execute(['id_estudiante' => $id]);
            $current = $statement->fetch();
            if (!is_array($current)) {
                $this->database->rollBack();
                return false;
            }

            $carnet = $current['apellidos'] !== $data['apellidos']
                ? Identificador::generarCarnet($this->database, (string) $data['apellidos'], 'estudiantes')
                : $current['carnet'];
            $statement = $this->database->prepare(
                'UPDATE usuarios AS u
                 INNER JOIN estudiantes AS e ON e.id_usuario = u.id_usuario
                 SET u.correo = :correo, u.nombres = :nombres, u.apellidos = :apellidos,
                     u.telefono = :telefono, u.edad = :edad, u.genero = :genero,
                     e.carnet = :carnet
                 WHERE e.id_estudiante = :id_estudiante'
            );
            $statement->execute([
                'correo' => $data['correo'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'],
                'edad' => $data['edad'],
                'genero' => $data['genero'],
                'carnet' => $carnet,
                'id_estudiante' => $id,
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
    public function delete(int $id): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT id_usuario FROM estudiantes
                 WHERE id_estudiante = :id_estudiante
                 FOR UPDATE'
            );
            $statement->execute(['id_estudiante' => $id]);
            $userId = $statement->fetchColumn();
            if ($userId === false) {
                $this->database->rollBack();
                return false;
            }

            $statement = $this->database->prepare(
                'SELECT COUNT(*) FROM inscripciones WHERE id_estudiante = :id_estudiante'
            );
            $statement->execute(['id_estudiante' => $id]);
            if ((int) $statement->fetchColumn() > 0) {
                throw new DomainException(
                    'No se puede eliminar un estudiante con historial de cursos o inscripciones. Puedes desactivar su cuenta para conservar esos registros.'
                );
            }

            $statement = $this->database->prepare(
                'DELETE FROM estudiantes WHERE id_estudiante = :id_estudiante'
            );
            $statement->execute(['id_estudiante' => $id]);
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

    /** Activa o desactiva la cuenta asociada al perfil estudiantil. */
    public function setStatus(int $id, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE usuarios AS u
             INNER JOIN estudiantes AS e ON e.id_usuario = u.id_usuario
             SET u.estado_cuenta = :estado_cuenta
             WHERE e.id_estudiante = :id_estudiante'
        );
        $statement->execute([
            'estado_cuenta' => $status,
            'id_estudiante' => $id,
        ]);

        return $this->exists($id);
    }

    /** Comprueba si existe un perfil estudiantil por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM estudiantes WHERE id_estudiante = :id_estudiante'
        );
        $statement->execute(['id_estudiante' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
