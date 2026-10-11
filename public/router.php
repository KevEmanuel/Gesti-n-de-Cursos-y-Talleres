<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicRoot = realpath(__DIR__);
$requestedFile = realpath($publicRoot . DIRECTORY_SEPARATOR . ltrim(
    str_replace('/', DIRECTORY_SEPARATOR, $requestPath),
    DIRECTORY_SEPARATOR
));
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico'];

if (
    $requestedFile !== false
    && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
    && in_array(strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION)), $staticExtensions, true)
) {
    return false;
}

require $publicRoot . DIRECTORY_SEPARATOR . 'index.php';
