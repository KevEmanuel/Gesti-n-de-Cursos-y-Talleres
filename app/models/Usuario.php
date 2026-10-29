<?php

declare(strict_types=1);

/**
 * Crea cuentas y resuelve las credenciales y roles de los usuarios.
 */
final class Usuario extends BaseModel
{
    /**
     * Comprueba si ya existe una cuenta administradora.
     */
    public function hasAdministrator(): bool
    {
        return (bool) $this->database
            ->query('SELECT EXISTS(SELECT 1 FROM administradores)')
            ->fetchColumn();
    }

    /**
     * Valida y crea la primera cuenta administradora.
     */
    public function createFirstAdministrator(
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): void {
        $this->database->beginTransaction();

        try {
            $lock = $this->database->query(
                "SELECT GET_LOCK('gestiondedatos_initial_administrator', 5)"
            )->fetchColumn();

            if ((int) $lock !== 1) {
                throw new RuntimeException('No se pudo reservar el alta inicial de administrador.');
            }

            if ($this->hasAdministrator()) {
                throw new DomainException('El administrador inicial ya fue creado.');
            }

            $statement = $this->database->prepare(
                'INSERT INTO usuarios
                    (Correo, Password, Nombres, Apellidos, Estado_Cuenta)
                 VALUES
                    (:Correo, :Password, :Nombres, :Apellidos, :Estado_Cuenta)'
            );
            $statement->execute([
                'Correo' => $email,
                'Password' => password_hash($password, PASSWORD_DEFAULT),
                'Nombres' => $firstName,
                'Apellidos' => $lastName,
                'Estado_Cuenta' => 'Activo',
            ]);

            $userId = (int) $this->database->lastInsertId();
            $statement = $this->database->prepare(
                'INSERT INTO administradores (IdUsuario) VALUES (:IdUsuario)'
            );
            $statement->execute(['IdUsuario' => $userId]);

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        } finally {
            $this->database->query(
                "SELECT RELEASE_LOCK('gestiondedatos_initial_administrator')"
            );
        }
    }

    /**
     * Busca la cuenta por Correo y determina su unico rol asociado.
     */
    public function byCredentials(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT u.IdUsuario, u.Correo, u.Password, u.Nombres, u.Apellidos,
                    u.Estado_Cuenta, a.IdAdmin, e.IdEstudiante, i.IdInstructor
             FROM usuarios AS u
             LEFT JOIN administradores AS a ON a.IdUsuario = u.IdUsuario
             LEFT JOIN estudiantes AS e ON e.IdUsuario = u.IdUsuario
             LEFT JOIN instructores AS i ON i.IdUsuario = u.IdUsuario
             WHERE u.Correo = :Correo
             LIMIT 1'
        );
        $statement->execute(['Correo' => $email]);
        $user = $statement->fetch();

        if (!is_array($user)) {
            return null;
        }

        $roles = [];
        if ($user['IdAdmin'] !== null) {
            $roles[] = ['name' => 'administrator', 'id' => (int) $user['IdAdmin']];
        }
        if ($user['IdEstudiante'] !== null) {
            $roles[] = ['name' => 'student', 'id' => (int) $user['IdEstudiante']];
        }
        if ($user['IdInstructor'] !== null) {
            $roles[] = ['name' => 'instructor', 'id' => (int) $user['IdInstructor']];
        }

        if (count($roles) !== 1) {
            return null;
        }

        $user['role'] = $roles[0]['name'];
        $user['role_id'] = $roles[0]['id'];

        return $user;
    }

    /**
     * Cambia la contraseña de un estudiante o instructor desde el panel administrativo.
     */
    public function setManagedPassword(string $role, int $profileId, string $passwordHash): bool
    {
        $profile = match ($role) {
            'student' => ['table' => 'estudiantes', 'id' => 'IdEstudiante'],
            'instructor' => ['table' => 'instructores', 'id' => 'IdInstructor'],
            default => null,
        };

        if ($profile === null) {
            return false;
        }

        $this->database->beginTransaction();

        try {
            $findUser = $this->database->prepare(
                'SELECT u.IdUsuario
                 FROM usuarios AS u
                 INNER JOIN ' . $profile['table'] . ' AS p ON p.IdUsuario = u.IdUsuario
                 WHERE p.' . $profile['id'] . ' = :profile_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $findUser->execute(['profile_id' => $profileId]);
            $userId = $findUser->fetchColumn();

            if ($userId === false) {
                $this->database->commit();
                return false;
            }

            $updatePassword = $this->database->prepare(
                'UPDATE usuarios
                 SET Password = :Password
                 WHERE IdUsuario = :IdUsuario'
            );
            $updatePassword->execute([
                'Password' => $passwordHash,
                'IdUsuario' => (int) $userId,
            ]);

            $resetTableExists = $this->database->prepare(
                'SELECT EXISTS(
                    SELECT 1
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                      AND table_name = :table_name
                )'
            );
            $resetTableExists->execute(['table_name' => 'password_reset_tokens']);

            if ((bool) $resetTableExists->fetchColumn()) {
                $invalidateTokens = $this->database->prepare(
                    'UPDATE password_reset_tokens
                     SET UsedAt = CURRENT_TIMESTAMP
                     WHERE IdUsuario = :IdUsuario AND UsedAt IS NULL'
                );
                $invalidateTokens->execute(['IdUsuario' => (int) $userId]);
            }

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
     * Registra un token temporal para una cuenta válida y limita solicitudes.
     */
    public function createPasswordResetToken(string $email, string $tokenHash): ?array
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT u.IdUsuario, u.Correo, u.Nombres, u.Apellidos,
                        a.IdAdmin, e.IdEstudiante, i.IdInstructor
                 FROM usuarios AS u
                 LEFT JOIN administradores AS a ON a.IdUsuario = u.IdUsuario
                 LEFT JOIN estudiantes AS e ON e.IdUsuario = u.IdUsuario
                 LEFT JOIN instructores AS i ON i.IdUsuario = u.IdUsuario
                 WHERE u.Correo = :Correo
                 LIMIT 1
                 FOR UPDATE'
            );
            $statement->execute(['Correo' => $email]);
            $user = $statement->fetch();

            $roleCount = is_array($user)
                ? (int) ($user['IdAdmin'] !== null)
                    + (int) ($user['IdEstudiante'] !== null)
                    + (int) ($user['IdInstructor'] !== null)
                : 0;

            if (!is_array($user) || $roleCount !== 1) {
                $this->database->commit();
                return null;
            }

            $cleanup = $this->database->prepare(
                'DELETE FROM password_reset_tokens
                 WHERE IdUsuario = :IdUsuario
                   AND CreatedAt < CURRENT_TIMESTAMP - INTERVAL 1 DAY'
            );
            $cleanup->execute(['IdUsuario' => (int) $user['IdUsuario']]);

            $rateLimit = $this->database->prepare(
                'SELECT COUNT(*)
                 FROM password_reset_tokens
                 WHERE IdUsuario = :IdUsuario
                   AND CreatedAt >= CURRENT_TIMESTAMP - INTERVAL 1 HOUR'
            );
            $rateLimit->execute(['IdUsuario' => (int) $user['IdUsuario']]);

            if ((int) $rateLimit->fetchColumn() >= 3) {
                $this->database->commit();
                return null;
            }

            $invalidate = $this->database->prepare(
                'UPDATE password_reset_tokens
                 SET UsedAt = CURRENT_TIMESTAMP
                 WHERE IdUsuario = :IdUsuario AND UsedAt IS NULL'
            );
            $invalidate->execute(['IdUsuario' => (int) $user['IdUsuario']]);

            $insert = $this->database->prepare(
                'INSERT INTO password_reset_tokens
                    (IdUsuario, TokenHash, ExpiresAt)
                 VALUES
                    (:IdUsuario, :TokenHash, CURRENT_TIMESTAMP + INTERVAL 1 HOUR)'
            );
            $insert->execute([
                'IdUsuario' => (int) $user['IdUsuario'],
                'TokenHash' => $tokenHash,
            ]);
            $this->database->commit();

            return [
                'Correo' => (string) $user['Correo'],
                'Nombres' => (string) $user['Nombres'],
                'Apellidos' => (string) $user['Apellidos'],
            ];
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Confirma si el token temporal existe y aún puede utilizarse.
     */
    public function hasValidPasswordResetToken(string $tokenHash): bool
    {
        $statement = $this->database->prepare(
            'SELECT EXISTS(
                SELECT 1 FROM password_reset_tokens
                WHERE TokenHash = :TokenHash
                  AND UsedAt IS NULL
                  AND ExpiresAt > CURRENT_TIMESTAMP
            )'
        );
        $statement->execute(['TokenHash' => $tokenHash]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Cambia la contraseña una sola vez e invalida los demás tokens vigentes.
     */
    public function resetPassword(string $tokenHash, string $passwordHash): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'SELECT IdPasswordResetToken, IdUsuario
                 FROM password_reset_tokens
                 WHERE TokenHash = :TokenHash
                   AND UsedAt IS NULL
                   AND ExpiresAt > CURRENT_TIMESTAMP
                 LIMIT 1
                 FOR UPDATE'
            );
            $statement->execute(['TokenHash' => $tokenHash]);
            $token = $statement->fetch();

            if (!is_array($token)) {
                $this->database->commit();
                return false;
            }

            $updateUser = $this->database->prepare(
                'UPDATE usuarios
                 SET Password = :Password
                 WHERE IdUsuario = :IdUsuario'
            );
            $updateUser->execute([
                'Password' => $passwordHash,
                'IdUsuario' => (int) $token['IdUsuario'],
            ]);

            $consumeTokens = $this->database->prepare(
                'UPDATE password_reset_tokens
                 SET UsedAt = CURRENT_TIMESTAMP
                 WHERE IdUsuario = :IdUsuario AND UsedAt IS NULL'
            );
            $consumeTokens->execute(['IdUsuario' => (int) $token['IdUsuario']]);
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
     * Revoca un token si no se pudo entregar el mensaje de recuperación.
     */
    public function revokePasswordResetToken(string $tokenHash): void
    {
        $statement = $this->database->prepare(
            'UPDATE password_reset_tokens
             SET UsedAt = CURRENT_TIMESTAMP
             WHERE TokenHash = :TokenHash AND UsedAt IS NULL'
        );
        $statement->execute(['TokenHash' => $tokenHash]);
    }
}
