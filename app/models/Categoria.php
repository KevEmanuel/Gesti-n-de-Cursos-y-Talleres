<?php

declare(strict_types=1);

/**
 * Consulta y mantiene las categorias de cursos en la base de datos.
 */
final class Categoria extends BaseModel
{
    protected function tableName(): string
    {
        return 'categorias';
    }

    /**
     * Obtiene los registros del modulo para su consulta.
     */
    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT id_categoria, nombre, descripcion, estado
             FROM categorias
             ORDER BY nombre'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene unicamente las categorias activas.
     */
    public function allActive(): array
    {
        $statement = $this->database->query(
            "SELECT id_categoria, nombre
             FROM categorias
             WHERE estado = 'Activo'
             ORDER BY nombre"
        );

        return $statement->fetchAll();
    }

    /**
     * Comprueba si el nombre ya esta registrado, excluyendo el identificador indicado.
     */
    public function nameExists(string $name, ?int $excludedId = null): bool
    {
        $sql = 'SELECT 1 FROM categorias WHERE nombre = :nombre';
        $parameters = ['nombre' => $name];

        if ($excludedId !== null) {
            $sql .= ' AND id_categoria <> :id_categoria';
            $parameters['id_categoria'] = $excludedId;
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Valida y registra un nuevo elemento del modulo.
     */
    public function create(string $name, ?string $description): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)'
        );
        $statement->execute([
            'nombre' => $name,
            'descripcion' => $description,
        ]);
    }

    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
    public function update(int $id, string $name, ?string $description, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE categorias
             SET nombre = :nombre, descripcion = :descripcion, estado = :estado
             WHERE id_categoria = :id_categoria'
        );
        $statement->execute([
            'nombre' => $name,
            'descripcion' => $description,
            'estado' => $status,
            'id_categoria' => $id,
        ]);

        return $statement->rowCount() > 0 || $this->exists($id);
    }

    /**
     * Actualiza el estado del registro y aplica las reglas del modulo.
     */
    public function setStatus(int $id, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE categorias SET estado = :estado WHERE id_categoria = :id_categoria'
        );
        $statement->execute([
            'estado' => $status,
            'id_categoria' => $id,
        ]);

        return $statement->rowCount() > 0 || $this->exists($id);
    }

    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM categorias WHERE id_categoria = :id_categoria'
        );
        $statement->execute(['id_categoria' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
