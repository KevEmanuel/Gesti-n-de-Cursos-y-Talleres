<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';

$password = fgets(STDIN);
if ($password === false) {
    fwrite(STDERR, "No se recibió una contraseña.\n");
    exit(1);
}

$password = rtrim($password, "\r\n");
if (strlen($password) < 12) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}

$database = getDatabaseConnection();
$database->beginTransaction();

try {
    $administrators = $database->query(
        'SELECT u.id_usuario
         FROM usuarios AS u
         INNER JOIN administradores AS a ON a.id_usuario = u.id_usuario
         FOR UPDATE'
    )->fetchAll(PDO::FETCH_COLUMN);

    if (count($administrators) !== 1) {
        throw new RuntimeException(
            'El restablecimiento requiere exactamente un administrador; no se realizaron cambios.'
        );
    }

    $statement = $database->prepare(
        'UPDATE usuarios
         SET password_hash = :password_hash
         WHERE id_usuario = :id_usuario'
    );
    $statement->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id_usuario' => (int) $administrators[0],
    ]);

    $database->commit();
    fwrite(STDOUT, "Contraseña del administrador restablecida correctamente.\n");
} catch (Throwable $exception) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
} finally {
    unset($password);
}
