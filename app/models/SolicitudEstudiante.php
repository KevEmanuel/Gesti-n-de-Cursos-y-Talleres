<?php

declare(strict_types=1);

/**
 * Registra y procesa solicitudes de creacion de cuentas estudiantiles.
 */
final class SolicitudEstudiante extends BaseModel
{
    /** Obtiene solicitudes de cuenta ordenadas primero por las pendientes de revisión. */
    public function all(): array
    {
        return $this->database->query(
            'SELECT id_solicitud_estudiante, correo, nombres, apellidos,
                    telefono, edad, genero, estado_solicitud, creada_en
             FROM solicitudes_estudiante
             ORDER BY CASE WHEN estado_solicitud = \'Pendiente\' THEN 0 ELSE 1 END,
                      creada_en DESC'
        )->fetchAll();
    }

    /** Registra una nueva solicitud de cuenta estudiantil con contraseña cifrada. */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'SELECT 1
             FROM usuarios AS u
             WHERE u.correo = :correo
             LIMIT 1'
        );
        $statement->execute(['correo' => $data['correo']]);
        if ($statement->fetchColumn() !== false) {
            throw new DomainException('El correo ya está registrado.');
        }

        $statement = $this->database->prepare(
            'SELECT 1
             FROM solicitudes_estudiante
             WHERE estado_solicitud = \'Pendiente\'
               AND correo = :correo
             LIMIT 1'
        );
        $statement->execute(['correo' => $data['correo']]);
        if ($statement->fetchColumn() !== false) {
            throw new DomainException('Ya existe una solicitud pendiente con ese correo.');
        }

        $statement = $this->database->prepare(
            'INSERT INTO solicitudes_estudiante
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
    }

    /**
     * Cambia el estado de la solicitud dentro de una transaccion.
     */
    public function changeStatus(int $applicationId, string $status, int $administratorId): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT correo, password_hash, nombres, apellidos, telefono, edad, genero,
                        estado_solicitud
                 FROM solicitudes_estudiante
                 WHERE id_solicitud_estudiante = :id_solicitud_estudiante
                 FOR UPDATE'
            );
            $statement->execute(['id_solicitud_estudiante' => $applicationId]);
            $application = $statement->fetch();

            if (!is_array($application)) {
                $this->database->rollBack();
                return false;
            }
            if ($application['estado_solicitud'] !== 'Pendiente') {
                throw new DomainException('Solo se pueden revisar solicitudes pendientes.');
            }

            if ($status === 'Aprobada') {
                $statement = $this->database->prepare(
                    'INSERT INTO usuarios
                        (correo, password_hash, nombres, apellidos, telefono, edad, genero)
                     VALUES
                        (:correo, :password_hash, :nombres, :apellidos, :telefono, :edad, :genero)'
                );
                $statement->execute([
                    'correo' => $application['correo'],
                    'password_hash' => $application['password_hash'],
                    'nombres' => $application['nombres'],
                    'apellidos' => $application['apellidos'],
                    'telefono' => $application['telefono'],
                    'edad' => $application['edad'],
                    'genero' => $application['genero'],
                ]);

                $statement = $this->database->prepare(
                    'INSERT INTO estudiantes (id_usuario, carnet)
                     VALUES (:id_usuario, :carnet)'
                );
                $statement->execute([
                    'id_usuario' => (int) $this->database->lastInsertId(),
                    'carnet' => Identificador::generarCarnet(
                        $this->database,
                        (string) $application['apellidos'],
                        'estudiantes'
                    ),
                ]);
            }

            $statement = $this->database->prepare(
                'UPDATE solicitudes_estudiante
                 SET estado_solicitud = :estado_solicitud,
                     id_admin_revisor = :id_admin_revisor,
                     revisada_en = CURRENT_TIMESTAMP,
                     password_hash = NULL
                 WHERE id_solicitud_estudiante = :id_solicitud_estudiante'
            );
            $statement->execute([
                'estado_solicitud' => $status,
                'id_admin_revisor' => $administratorId,
                'id_solicitud_estudiante' => $applicationId,
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
}
