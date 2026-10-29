<?php

declare(strict_types=1);

/**
 * Genera y verifica identificadores unicos para estudiantes e instructores.
 */
final class Identificador
{
    /**
     * Genera un Carnet disponible usando iniciales de Apellidos y un sufijo numérico.
     *
     * @param PDO $database Conexión usada para consultar carnets ya registrados.
     * @param string $Apellidos Apellidos que determinan el prefijo del Carnet.
     * @param string $tabla Tabla del perfil, limitada a estudiantes o instructores.
     * @return string Carnet único de dos letras y cuatro dígitos.
     *
     * @throws InvalidArgumentException Si la tabla no corresponde a un perfil permitido.
     * @throws DomainException Si no se pueden obtener iniciales o no quedan carnets disponibles.
     */
    public static function generarCarnet(PDO $database, string $Apellidos, string $tabla): string
    {
        if (!in_array($tabla, ['estudiantes', 'instructores'], true)) {
            throw new InvalidArgumentException('El tipo de perfil no admite un Carnet.');
        }

        $parts = preg_split('/\s+/u', trim($Apellidos), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            throw new DomainException('Los Apellidos deben contener al menos una letra para generar el Carnet.');
        }

        $surnameLetters = [];
        foreach ($parts as $part) {
            $letters = preg_replace('/[^\p{L}]/u', '', $part);
            if ($letters !== false && $letters !== '') {
                $surnameLetters[] = $letters;
            }
        }
        if ($surnameLetters === []) {
            throw new DomainException('Los Apellidos deben contener al menos una letra para generar el Carnet.');
        }

        if (count($surnameLetters) > 1) {
            $prefix = mb_substr($surnameLetters[0], 0, 1, 'UTF-8')
                . mb_substr($surnameLetters[1], 0, 1, 'UTF-8');
        } else {
            $prefix = mb_substr($surnameLetters[0], 0, 2, 'UTF-8');
            if (mb_strlen($prefix, 'UTF-8') === 1) {
                $prefix .= 'X';
            }
        }
        $prefix = mb_strtoupper($prefix, 'UTF-8');

        $statement = $database->prepare(
            'SELECT Carnet FROM estudiantes WHERE Carnet LIKE :patron_estudiante
             UNION ALL
             SELECT Carnet FROM instructores WHERE Carnet LIKE :patron_instructor'
        );
        $statement->execute([
            'patron_estudiante' => $prefix . '%',
            'patron_instructor' => $prefix . '%',
        ]);
        $used = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $Carnet) {
            $Carnet = (string) $Carnet;
            $suffix = mb_substr($Carnet, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8');
            if (preg_match('/^\d{4}$/', $suffix) === 1) {
                $used[$suffix] = true;
            }
        }

        $available = [];
        for ($number = 0; $number <= 9999; $number++) {
            $suffix = str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            if (!isset($used[$suffix])) {
                $available[] = $suffix;
            }
        }
        if ($available === []) {
            throw new DomainException('No quedan carnets disponibles para los Apellidos indicados.');
        }

        return $prefix . $available[random_int(0, count($available) - 1)];
    }
}
