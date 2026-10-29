<?php

declare(strict_types=1);

/**
 * Consulta y mantiene las categorias de cursos en la base de datos.
 */
final class Categoria extends BaseModel
{
    /** Obtiene todas las categorías ordenadas por Nombre. */
    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT IdCategoria, Nombre, Descripcion, Estado
             FROM categorias
             ORDER BY Nombre'
        );

        return $statement->fetchAll();
    }

    /**
     * Obtiene unicamente las categorias activas.
     */
    public function allActive(): array
    {
        $statement = $this->database->query(
            "SELECT IdCategoria, Nombre
             FROM categorias
             WHERE Estado = 'Activo'
             ORDER BY Nombre"
        );

        return $statement->fetchAll();
    }

    /**
     * Comprueba si el Nombre ya esta registrado, excluyendo el identificador indicado.
     */
    public function nameExists(string $name, ?int $excludedId = null): bool
    {
        $sql = 'SELECT 1 FROM categorias WHERE Nombre = :Nombre';
        $parameters = ['Nombre' => $name];

        if ($excludedId !== null) {
            $sql .= ' AND IdCategoria <> :IdCategoria';
            $parameters['IdCategoria'] = $excludedId;
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /** Inserta una categoría nueva con su descripción opcional. */
    public function create(string $name, ?string $description): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO categorias (Nombre, Descripcion) VALUES (:Nombre, :Descripcion)'
        );
        $statement->execute([
            'Nombre' => $name,
            'Descripcion' => $description,
        ]);
    }

    /** Actualiza Nombre, descripción y Estado de una categoría existente. */
    public function update(int $id, string $name, ?string $description, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE categorias
             SET Nombre = :Nombre, Descripcion = :Descripcion, Estado = :Estado
             WHERE IdCategoria = :IdCategoria'
        );
        $statement->execute([
            'Nombre' => $name,
            'Descripcion' => $description,
            'Estado' => $status,
            'IdCategoria' => $id,
        ]);

        return $statement->rowCount() > 0 || $this->exists($id);
    }

    /** Guarda el Estado de una categoría y confirma si el identificador existe. */
    public function setStatus(int $id, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE categorias SET Estado = :Estado WHERE IdCategoria = :IdCategoria'
        );
        $statement->execute([
            'Estado' => $status,
            'IdCategoria' => $id,
        ]);

        return $statement->rowCount() > 0 || $this->exists($id);
    }

    /** Comprueba si existe una categoría por su identificador. */
    private function exists(int $id): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM categorias WHERE IdCategoria = :IdCategoria'
        );
        $statement->execute(['IdCategoria' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
