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
        $report = $this->getReportData();

        $this->render('reportes', [
            'title' => 'Reporte de Datos de cursos e inscripciones',
            'categories' => $this->reports->categories(),
            'rows' => $report['rows'],
            'filters' => $report['filters'],
            'error' => $report['error'],
        ]);
    }

    /** para generar un  PDF con los resultados y filtros del reporte actual. */
    public function exportPdf(): void
    {
        $this->requireAdministrator();
        $report = $this->getReportData();

        if ($report['error'] !== null) {
            http_response_code(422);
            header('Content-Type: text/plain; charset=UTF-8');
            echo $report['error'];
            return;
        }

        $categoryName = 'Todas';
        foreach ($this->reports->categories() as $category) {
            if ((string) $category['IdCategoria'] === $report['filters']['IdCategoria']) {
                $categoryName = (string) $category['Nombre'];
                break;
            }
        }

        $filters = $report['filters'];
        $modality = $filters['Modalidad'] === 'Hibrido' ? 'Híbrido' : $filters['Modalidad'];
        $data = [
            'rows' => $report['rows'],
            'fechaInicio' => $filters['FechaInicio'] !== '' ? $filters['FechaInicio'] : 'Sin límite',
            'fechaCierre' => $filters['FechaCierre'] !== '' ? $filters['FechaCierre'] : 'Sin límite',
            'categoryName' => $categoryName,
            'modality' => $modality !== '' ? $modality : 'Todas',
            'generatedAt' => date('d/m/Y H:i'),
        ];

        ob_start();
        try {
            extract($data, EXTR_SKIP);
            require __DIR__ . '/../views/reportes/pdf.php';
            $html = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $pdf = new \Dompdf\Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        $pdf->stream('reporte-cursos.pdf', ['Attachment' => true]);
    }

    /**
     * Valida filtros y consulta las filas compartidas por la página y el PDF.
     *
     * @return array{filters: array{FechaInicio: string, FechaCierre: string, IdCategoria: string, Modalidad: string}, error: ?string, rows: array}
     */

    /**La funcion solo permite exportar reportes en formato PDF de los cursos en general
     * NO permite exportar datos escenciales como estudiantes datos por cursos perosnales
     * se implementara en futuras actualizaicones 
     */

    private function getReportData(): array
    {
        $filters = [
            'FechaInicio' => trim((string) ($_GET['FechaInicio'] ?? '')),
            'FechaCierre' => trim((string) ($_GET['FechaCierre'] ?? '')),
            'IdCategoria' => trim((string) ($_GET['IdCategoria'] ?? '')),
            'Modalidad' => trim((string) ($_GET['Modalidad'] ?? '')),
        ];

        if (($filters['FechaInicio'] !== '' && !$this->validDate($filters['FechaInicio']))
            || ($filters['FechaCierre'] !== '' && !$this->validDate($filters['FechaCierre']))) {
            http_response_code(422);
            $error = 'Las fechas del filtro no son válidas.';
            $rows = [];
        } elseif (
            $filters['FechaInicio'] !== ''
            && $filters['FechaCierre'] !== ''
            && $filters['FechaCierre'] < $filters['FechaInicio']
        ) {
            http_response_code(422);
            $error = 'La fecha final debe ser igual o posterior a la inicial.';
            $rows = [];
        } elseif ($filters['IdCategoria'] !== '' && !ctype_digit($filters['IdCategoria'])) {
            http_response_code(422);
            $error = 'Selecciona una categoría válida.';
            $rows = [];
        } elseif ($filters['Modalidad'] !== ''
            && !in_array($filters['Modalidad'], ['Presencial', 'Virtual', 'Hibrido'], true)) {
            http_response_code(422);
            $error = 'Selecciona una Modalidad válida.';
            $rows = [];
        } else {
            $error = null;
            $rows = $this->reports->courseOccupancy($filters);
        }

        return compact('filters', 'error', 'rows');
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
