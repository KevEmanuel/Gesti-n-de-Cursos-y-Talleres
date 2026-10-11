<?php

declare(strict_types=1);

/**
 * Crea cuentas y resuelve las credenciales y roles de los usuarios.
 */
final class Usuario extends BaseModel
{
    protected function tableName(): string
    {
        return 'usuarios';
    }

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
}
