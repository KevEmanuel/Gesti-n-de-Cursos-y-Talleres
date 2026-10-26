<?php

declare(strict_types=1);

/**
 * Escapa un valor para mostrarlo como texto en un documento HTML.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
