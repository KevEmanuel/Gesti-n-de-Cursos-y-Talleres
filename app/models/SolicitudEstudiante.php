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
            'SELECT IdSolicitudEstudiante, Correo, Nombres, Apellidos,
                    Telefono, Edad, Genero, EstadoSolicitud, CreadaEn
             FROM solicitudes_estudiante
             ORDER BY CASE WHEN EstadoSolicitud = \'Pendiente\' THEN 0 ELSE 1 END,
                      CreadaEn DESC'
        )->fetchAll();
    }

    /** Registra una nueva solicitud de cuenta estudiantil con contraseña cifrada. */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'SELECT 1
             FROM usuarios AS u
             WHERE u.Correo = :Correo
             LIMIT 1'
        );
        $statement->execute(['Correo' => $data['Correo']]);
        if ($statement->fetchColumn() !== false) {
            throw new DomainException('El Correo ya está registrado.');
        }

        $statement = $this->database->prepare(
            'SELECT 1
             FROM solicitudes_estudiante
             WHERE EstadoSolicitud = \'Pendiente\'
               AND Correo = :Correo
             LIMIT 1'
        );
        $statement->execute(['Correo' => $data['Correo']]);
        if ($statement->fetchColumn() !== false) {
            throw new DomainException('Ya existe una solicitud pendiente con ese Correo.');
        }

        $statement = $this->database->prepare(
            'INSERT INTO solicitudes_estudiante
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
    }

    /**
     * Cambia el Estado de la solicitud dentro de una transaccion.
     */
    public function changeStatus(int $applicationId, string $status, int $administratorId): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT Correo, Password, Nombres, Apellidos, Telefono, Edad, Genero,
                        EstadoSolicitud
                 FROM solicitudes_estudiante
                 WHERE IdSolicitudEstudiante = :IdSolicitudEstudiante
                 FOR UPDATE'
            );
            $statement->execute(['IdSolicitudEstudiante' => $applicationId]);
            $application = $statement->fetch();

            if (!is_array($application)) {
                $this->database->rollBack();
                return false;
            }
            if ($application['EstadoSolicitud'] !== 'Pendiente') {
                throw new DomainException('Solo se pueden revisar solicitudes pendientes.');
            }

            if ($status === 'Aprobada') {
                $statement = $this->database->prepare(
                    'INSERT INTO usuarios
                        (Correo, Password, Nombres, Apellidos, Telefono, Edad, Genero)
                     VALUES
                        (:Correo, :Password, :Nombres, :Apellidos, :Telefono, :Edad, :Genero)'
                );
                $statement->execute([
                    'Correo' => $application['Correo'],
                    'Password' => $application['Password'],
                    'Nombres' => $application['Nombres'],
                    'Apellidos' => $application['Apellidos'],
                    'Telefono' => $application['Telefono'],
                    'Edad' => $application['Edad'],
                    'Genero' => $application['Genero'],
                ]);

                $statement = $this->database->prepare(
                    'INSERT INTO estudiantes (IdUsuario, Carnet)
                     VALUES (:IdUsuario, :Carnet)'
                );
                $statement->execute([
                    'IdUsuario' => (int) $this->database->lastInsertId(),
                    'Carnet' => Identificador::generarCarnet(
                        $this->database,
                        (string) $application['Apellidos'],
                        'estudiantes'
                    ),
                ]);
            }

            $statement = $this->database->prepare(
                'UPDATE solicitudes_estudiante
                 SET EstadoSolicitud = :EstadoSolicitud,
                     IdAdminRevisor = :IdAdminRevisor,
                     RevisadaEn = CURRENT_TIMESTAMP,
                     Password = NULL
                 WHERE IdSolicitudEstudiante = :IdSolicitudEstudiante'
            );
            $statement->execute([
                'EstadoSolicitud' => $status,
                'IdAdminRevisor' => $administratorId,
                'IdSolicitudEstudiante' => $applicationId,
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
