<?php

declare(strict_types=1);

/**
 * Funcion para insertar contenido de forma segura en HTML.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
