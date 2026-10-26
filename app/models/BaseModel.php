<?php

declare(strict_types=1);

/**
 * Proporciona la conexion PDO compartida por los modelos de datos.
 */
abstract class BaseModel
{
    /** Proporciona a cada modelo la conexión PDO de la solicitud. */
    public function __construct(protected PDO $database)
    {
    }
}
