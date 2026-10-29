<?php

declare(strict_types=1);

/**
 * Valida las solicitudes y coordina la gestion de periodos de inscripcion.
 */
final class PeriodoController extends BaseController
{
    private Periodo $periods;

    /** Prepara el modelo de períodos con la conexión PDO de la aplicación. */
    public function __construct()
    {
        $this->periods = new Periodo(getDatabaseConnection());
    }

    /** Muestra los períodos de inscripción y cursos administrables. */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('periodos', $this->viewData());
    }

    /** Valida fechas y curso, impide traslapes y registra un período nuevo. */
    public function create(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $data = $this->submittedData();
        $error = $this->validate($data);

        if ($error !== null) {
            $this->renderError($error, $data);
            return;
        }
        if ($this->periods->hasOverlappingPeriod($data['IdCurso'], $data['FechaInicio'], $data['FechaCierre'])) {
            $this->renderError('Ya existe un período con fechas superpuestas para ese curso.', $data);
            return;
        }

        $data['IdAdmin'] = (int) $_SESSION['user']['administrator_id'];
        $this->periods->create($data);
        $this->redirect('/periodos?mensaje=creado');
    }

    /** Actualiza un período existente después de comprobar fechas */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdPeriodo'] ?? null, FILTER_VALIDATE_INT);
        $data = $this->submittedData();

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de período no válido.';
            return;
        }
        $error = $this->validate($data);
        if ($error !== null) {
            $this->renderError($error, $data);
            return;
        }
        if ($this->periods->hasOverlappingPeriod($data['IdCurso'], $data['FechaInicio'], $data['FechaCierre'], $id)) {
            $this->renderError('Ya existe un período con fechas superpuestas para ese curso.', $data);
            return;
        }
        if (!$this->periods->update($id, $data)) {
            http_response_code(404);
            echo 'El período solicitado no existe.';
            return;
        }

        $this->redirect('/periodos?mensaje=actualizado');
    }

    /** Elimina el período de inscripción indicado. */
    public function delete(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdPeriodo'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de período no válido.';
            return;
        }
        if (!$this->periods->delete($id)) {
            http_response_code(404);
            echo 'El período solicitado no existe.';
            return;
        }

        $this->redirect('/periodos?mensaje=eliminado');
    }

    /** Normaliza el curso y el rango de fechas enviados por el formulario. */
    private function submittedData(): array
    {
        return [
            'IdCurso' => filter_var($_POST['IdCurso'] ?? null, FILTER_VALIDATE_INT),
            'FechaInicio' => (string) ($_POST['FechaInicio'] ?? ''),
            'FechaCierre' => (string) ($_POST['FechaCierre'] ?? ''),
        ];
    }

    /**
     * Valida el curso y el formato y orden cronológico del período de inscripción.
     *
     * @param array<string, mixed> $data Datos del período enviados por el formulario.
     * @return string|null Mensaje de validación o null si los datos son válidos.
     */
    private function validate(array $data): ?string
    {
        if ($data['IdCurso'] === false || $data['IdCurso'] === null || $data['IdCurso'] < 1) {
            return 'Selecciona un curso válido.';
        }
        foreach (['FechaInicio', 'FechaCierre'] as $field) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data[$field]);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
                || $date->format('Y-m-d') !== $data[$field]) {
                return 'Ingresa fechas válidas.';
            }
        }
        if ($data['FechaCierre'] < $data['FechaInicio']) {
            return 'La fecha de cierre debe ser igual o posterior al inicio.';
        }

        return null;
    }

    /** Obtiene períodos, cursos y mensajes para la pantalla administrativa. */
    private function viewData(): array
    {
        $messages = [
            'creado' => 'Período creado correctamente.',
            'actualizado' => 'Período actualizado correctamente.',
            'eliminado' => 'Período eliminado correctamente.',
        ];

        return [
            'title' => 'Períodos de inscripción',
            'periods' => $this->periods->all(),
            'courses' => $this->periods->courses(),
            'csrfToken' => $this->csrfToken(),
            'message' => $messages[(string) ($_GET['mensaje'] ?? '')] ?? null,
            'error' => null,
            'old' => [],
        ];
    }

    /** Renderiza la gestión de períodos con el error y los datos introducidos. */
    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('periodos', $data);
    }
}
