<?php

declare(strict_types=1);

/**
 * Proporciona la conexion PDO compartida por los modelos de datos.
 */
abstract class BaseModel
{
    /** Comparte la conexión PDO con los modelos concretos. */
    public function __construct(protected PDO $database)
    {
    }

    /** Devuelve el nombre de la tabla principal del modelo. */
    abstract protected function tableName(): string;
}
