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
            'SELECT e.IdEstudiante, e.Carnet, u.IdUsuario, u.Correo, u.Nombres,
                    u.Apellidos, u.Telefono, u.Edad, u.Genero, u.Estado_Cuenta
             FROM estudiantes AS e
             INNER JOIN usuarios AS u ON u.IdUsuario = e.IdUsuario
             ORDER BY u.Apellidos, u.Nombres'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene los datos del perfil asociados a la cuenta estudiantil.
     */
    public function findForAccount(int $studentId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT e.IdEstudiante, e.Carnet, u.Correo, u.Nombres, u.Apellidos,
                    u.Telefono, u.Edad, u.Genero
             FROM estudiantes AS e
             INNER JOIN usuarios AS u ON u.IdUsuario = e.IdUsuario
             WHERE e.IdEstudiante = :IdEstudiante
               AND u.Estado_Cuenta = \'Activo\''
        );
        $statement->execute(['IdEstudiante' => $studentId]);
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
             INNER JOIN estudiantes AS e ON e.IdUsuario = u.IdUsuario
             SET u.Telefono = :Telefono, u.Edad = :Edad, u.Genero = :Genero
             WHERE e.IdEstudiante = :IdEstudiante
               AND u.Estado_Cuenta = \'Activo\''
        );
        $statement->execute([
            'Telefono' => $data['Telefono'],
            'Edad' => $data['Edad'],
            'Genero' => $data['Genero'],
            'IdEstudiante' => $studentId,
        ]);

        return $this->findForAccount($studentId) !== null;
    }

    /** Crea la cuenta y el perfil estudiantil, generando su Carnet en una transacción. */
    public function create(array $data): void
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'INSERT INTO usuarios
                    (Correo, Password, Nombres, Apellidos, Telefono, Edad, Genero)
                 VALUES
                    (:Correo, :Password, :Nombres, :Apellidos, :Telefono, :Edad, :Genero)'
            );
            $statement->execute([
                'Correo' => $data['Correo'],
                'Password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'Nombres' => $data['Nombres'],
                'Apellidos' => $data['Apellidos'],
                'Telefono' => $data['Telefono'],
                'Edad' => $data['Edad'],
                'Genero' => $data['Genero'],
            ]);

            $statement = $this->database->prepare(
                'INSERT INTO estudiantes (IdUsuario, Carnet)
                 VALUES (:IdUsuario, :Carnet)'
            );
            $statement->execute([
                'IdUsuario' => (int) $this->database->lastInsertId(),
                'Carnet' => Identificador::generarCarnet(
                    $this->database,
                    (string) $data['Apellidos'],
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

    /** Actualiza los datos de cuenta y perfil
     * regenera el Carnet si cambian Apellidos.
     **/
    public function update(int $id, array $data): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT e.Carnet, u.Apellidos
                 FROM estudiantes AS e
                 INNER JOIN usuarios AS u ON u.IdUsuario = e.IdUsuario
                 WHERE e.IdEstudiante = :IdEstudiante
                 FOR UPDATE'
            );
            $statement->execute(['IdEstudiante' => $id]);
            $current = $statement->fetch();
            if (!is_array($current)) {
                $this->database->rollBack();
                return false;
            }

            $Carnet = $current['Apellidos'] !== $data['Apellidos']
                ? Identificador::generarCarnet($this->database, (string) $data['Apellidos'], 'estudiantes')
                : $current['Carnet'];
            $statement = $this->database->prepare(
                'UPDATE usuarios AS u
                 INNER JOIN estudiantes AS e ON e.IdUsuario = u.IdUsuario
                 SET u.Correo = :Correo, u.Nombres = :Nombres, u.Apellidos = :Apellidos,
                     u.Telefono = :Telefono, u.Edad = :Edad, u.Genero = :Genero,
                     e.Carnet = :Carnet
                 WHERE e.IdEstudiante = :IdEstudiante'
            );
            $statement->execute([
                'Correo' => $data['Correo'],
                'Nombres' => $data['Nombres'],
                'Apellidos' => $data['Apellidos'],
                'Telefono' => $data['Telefono'],
                'Edad' => $data['Edad'],
                'Genero' => $data['Genero'],
                'Carnet' => $Carnet,
                'IdEstudiante' => $id,
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
                'SELECT IdUsuario FROM estudiantes
                 WHERE IdEstudiante = :IdEstudiante
                 FOR UPDATE'
            );
            $statement->execute(['IdEstudiante' => $id]);
            $userId = $statement->fetchColumn();
            if ($userId === false) {
                $this->database->rollBack();
                return false;
            }

            $statement = $this->database->prepare(
                'SELECT COUNT(*) FROM inscripciones WHERE IdEstudiante = :IdEstudiante'
            );
            $statement->execute(['IdEstudiante' => $id]);
            if ((int) $statement->fetchColumn() > 0) {
                throw new DomainException(
                    'No se puede eliminar un estudiante con historial de cursos o inscripciones. Puedes desactivar su cuenta para conservar esos registros.'
                );
            }

            $statement = $this->database->prepare(
                'DELETE FROM estudiantes WHERE IdEstudiante = :IdEstudiante'
            );
            $statement->execute(['IdEstudiante' => $id]);
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

    /** Activa o desactiva la cuenta asociada al perfil estudiantil. */
    public function setStatus(int $id, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE usuarios AS u
             INNER JOIN estudiantes AS e ON e.IdUsuario = u.IdUsuario
             SET u.Estado_Cuenta = :Estado_Cuenta
             WHERE e.IdEstudiante = :IdEstudiante'
        );
        $statement->execute([
            'Estado_Cuenta' => $status,
            'IdEstudiante' => $id,
        ]);

        return $this->exists($id);
    }

    /** Comprueba si existe un perfil estudiantil por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM estudiantes WHERE IdEstudiante = :IdEstudiante'
        );
        $statement->execute(['IdEstudiante' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
