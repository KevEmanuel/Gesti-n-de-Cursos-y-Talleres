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
                    (correo, password_hash, nombres, apellidos, estado_cuenta)
                 VALUES
                    (:correo, :password_hash, :nombres, :apellidos, :estado_cuenta)'
            );
            $statement->execute([
                'correo' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'nombres' => $firstName,
                'apellidos' => $lastName,
                'estado_cuenta' => 'Activo',
            ]);

            $userId = (int) $this->database->lastInsertId();
            $statement = $this->database->prepare(
                'INSERT INTO administradores (id_usuario) VALUES (:id_usuario)'
            );
            $statement->execute(['id_usuario' => $userId]);

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
     * Busca la cuenta por correo y determina su unico rol asociado.
     */
    public function byCredentials(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT u.id_usuario, u.correo, u.password_hash, u.nombres, u.apellidos,
                    u.estado_cuenta, a.id_admin, e.id_estudiante, i.id_instructor
             FROM usuarios AS u
             LEFT JOIN administradores AS a ON a.id_usuario = u.id_usuario
             LEFT JOIN estudiantes AS e ON e.id_usuario = u.id_usuario
             LEFT JOIN instructores AS i ON i.id_usuario = u.id_usuario
             WHERE u.correo = :correo
             LIMIT 1'
        );
        $statement->execute(['correo' => $email]);
        $user = $statement->fetch();

        if (!is_array($user)) {
            return null;
        }

        $roles = [];
        if ($user['id_admin'] !== null) {
            $roles[] = ['name' => 'administrator', 'id' => (int) $user['id_admin']];
        }
        if ($user['id_estudiante'] !== null) {
            $roles[] = ['name' => 'student', 'id' => (int) $user['id_estudiante']];
        }
        if ($user['id_instructor'] !== null) {
            $roles[] = ['name' => 'instructor', 'id' => (int) $user['id_instructor']];
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
            'student' => ['table' => 'estudiantes', 'id' => 'id_estudiante'],
            'instructor' => ['table' => 'instructores', 'id' => 'id_instructor'],
            default => null,
        };

        if ($profile === null) {
            return false;
        }

        $this->database->beginTransaction();

        try {
            $findUser = $this->database->prepare(
                'SELECT u.id_usuario
                 FROM usuarios AS u
                 INNER JOIN ' . $profile['table'] . ' AS p ON p.id_usuario = u.id_usuario
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
                 SET password_hash = :password_hash
                 WHERE id_usuario = :id_usuario'
            );
            $updatePassword->execute([
                'password_hash' => $passwordHash,
                'id_usuario' => (int) $userId,
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
                     SET used_at = CURRENT_TIMESTAMP
                     WHERE id_usuario = :id_usuario AND used_at IS NULL'
                );
                $invalidateTokens->execute(['id_usuario' => (int) $userId]);
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
                'SELECT u.id_usuario, u.correo, u.nombres, u.apellidos,
                        a.id_admin, e.id_estudiante, i.id_instructor
                 FROM usuarios AS u
                 LEFT JOIN administradores AS a ON a.id_usuario = u.id_usuario
                 LEFT JOIN estudiantes AS e ON e.id_usuario = u.id_usuario
                 LEFT JOIN instructores AS i ON i.id_usuario = u.id_usuario
                 WHERE u.correo = :correo
                 LIMIT 1
                 FOR UPDATE'
            );
            $statement->execute(['correo' => $email]);
            $user = $statement->fetch();

            $roleCount = is_array($user)
                ? (int) ($user['id_admin'] !== null)
                    + (int) ($user['id_estudiante'] !== null)
                    + (int) ($user['id_instructor'] !== null)
                : 0;

            if (!is_array($user) || $roleCount !== 1) {
                $this->database->commit();
                return null;
            }

            $cleanup = $this->database->prepare(
                'DELETE FROM password_reset_tokens
                 WHERE id_usuario = :id_usuario
                   AND created_at < CURRENT_TIMESTAMP - INTERVAL 1 DAY'
            );
            $cleanup->execute(['id_usuario' => (int) $user['id_usuario']]);

            $rateLimit = $this->database->prepare(
                'SELECT COUNT(*)
                 FROM password_reset_tokens
                 WHERE id_usuario = :id_usuario
                   AND created_at >= CURRENT_TIMESTAMP - INTERVAL 1 HOUR'
            );
            $rateLimit->execute(['id_usuario' => (int) $user['id_usuario']]);

            if ((int) $rateLimit->fetchColumn() >= 3) {
                $this->database->commit();
                return null;
            }

            $invalidate = $this->database->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = CURRENT_TIMESTAMP
                 WHERE id_usuario = :id_usuario AND used_at IS NULL'
            );
            $invalidate->execute(['id_usuario' => (int) $user['id_usuario']]);

            $insert = $this->database->prepare(
                'INSERT INTO password_reset_tokens
                    (id_usuario, token_hash, expires_at)
                 VALUES
                    (:id_usuario, :token_hash, CURRENT_TIMESTAMP + INTERVAL 1 HOUR)'
            );
            $insert->execute([
                'id_usuario' => (int) $user['id_usuario'],
                'token_hash' => $tokenHash,
            ]);
            $this->database->commit();

            return [
                'correo' => (string) $user['correo'],
                'nombres' => (string) $user['nombres'],
                'apellidos' => (string) $user['apellidos'],
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
                WHERE token_hash = :token_hash
                  AND used_at IS NULL
                  AND expires_at > CURRENT_TIMESTAMP
            )'
        );
        $statement->execute(['token_hash' => $tokenHash]);

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
                'SELECT id_password_reset_token, id_usuario
                 FROM password_reset_tokens
                 WHERE token_hash = :token_hash
                   AND used_at IS NULL
                   AND expires_at > CURRENT_TIMESTAMP
                 LIMIT 1
                 FOR UPDATE'
            );
            $statement->execute(['token_hash' => $tokenHash]);
            $token = $statement->fetch();

            if (!is_array($token)) {
                $this->database->commit();
                return false;
            }

            $updateUser = $this->database->prepare(
                'UPDATE usuarios
                 SET password_hash = :password_hash
                 WHERE id_usuario = :id_usuario'
            );
            $updateUser->execute([
                'password_hash' => $passwordHash,
                'id_usuario' => (int) $token['id_usuario'],
            ]);

            $consumeTokens = $this->database->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = CURRENT_TIMESTAMP
                 WHERE id_usuario = :id_usuario AND used_at IS NULL'
            );
            $consumeTokens->execute(['id_usuario' => (int) $token['id_usuario']]);
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
             SET used_at = CURRENT_TIMESTAMP
             WHERE token_hash = :token_hash AND used_at IS NULL'
        );
        $statement->execute(['token_hash' => $tokenHash]);
    }
}
