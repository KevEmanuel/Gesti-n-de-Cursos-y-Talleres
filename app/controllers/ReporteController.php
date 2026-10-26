<?php

declare(strict_types=1);

/**
 * Valida filtros y prepara los reportes de cursos e inscripciones.
 */
final class ReporteController extends BaseController
{
    private Reporte $reports;

    /** Prepara el modelo que consulta estadísticas de cursos e inscripciones. */
    public function __construct()
    {
        $this->reports = new Reporte(getDatabaseConnection());
    }

    /** Valida filtros y presenta el reporte de ocupación para administradores. */
    public function index(): void
    {
        $this->requireAdministrator();
        $filters = [
            'fecha_inicio' => trim((string) ($_GET['fecha_inicio'] ?? '')),
            'fecha_cierre' => trim((string) ($_GET['fecha_cierre'] ?? '')),
            'id_categoria' => trim((string) ($_GET['id_categoria'] ?? '')),
            'modalidad' => trim((string) ($_GET['modalidad'] ?? '')),
        ];

        if (($filters['fecha_inicio'] !== '' && !$this->validDate($filters['fecha_inicio']))
            || ($filters['fecha_cierre'] !== '' && !$this->validDate($filters['fecha_cierre']))) {
            http_response_code(422);
            $error = 'Las fechas del filtro no son válidas.';
            $rows = [];
        } elseif (
            $filters['fecha_inicio'] !== ''
            && $filters['fecha_cierre'] !== ''
            && $filters['fecha_cierre'] < $filters['fecha_inicio']
        ) {
            http_response_code(422);
            $error = 'La fecha final debe ser igual o posterior a la inicial.';
            $rows = [];
        } elseif ($filters['id_categoria'] !== '' && !ctype_digit($filters['id_categoria'])) {
            http_response_code(422);
            $error = 'Selecciona una categoría válida.';
            $rows = [];
        } elseif ($filters['modalidad'] !== ''
            && !in_array($filters['modalidad'], ['Presencial', 'Virtual', 'Hibrido'], true)) {
            http_response_code(422);
            $error = 'Selecciona una modalidad válida.';
            $rows = [];
        } else {
            $error = null;
            $rows = $this->reports->courseOccupancy($filters);
        }

        $this->render('reportes', [
            'title' => 'Reportes de ocupación',
            'categories' => $this->reports->categories(),
            'rows' => $rows,
            'filters' => $filters,
            'error' => $error,
        ]);
    }

    /** Comprueba que el valor sea una fecha real con formato exacto AAAA-MM-DD. */
    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
    }
}
