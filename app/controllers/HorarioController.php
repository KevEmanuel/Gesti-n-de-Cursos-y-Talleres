<?php

declare(strict_types=1);

/**
 * Valida las solicitudes y coordina la gestion de horarios de cursos.
 */
final class HorarioController extends BaseController
{
    private Horario $schedules;

    /** Prepara el modelo de horarios con la conexión PDO de la aplicación. */
    public function __construct()
    {
        $this->schedules = new Horario(getDatabaseConnection());
    }

    /** Muestra horarios y cursos disponibles para su administración. */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('horarios', $this->viewData());
    }

    /** Valida el horario y rechaza conflictos del instructor antes de crearlo. */
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

        if ($this->schedules->instructorConflict(
            $data['IdCurso'],
            $data['Dia'],
            $data['HoraInicio'],
            $data['Hora_finalizacion']
        )) {
            $this->renderError('El instructor ya tiene un curso asignado en ese horario.', $data);
            return;
        }

        $this->schedules->create($data);
        $this->redirect('/horarios?mensaje=creado');
    }

    /** Actualiza un horario si sus horas son válidas y no generan conflictos. */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdHorario'] ?? null, FILTER_VALIDATE_INT);
        $data = $this->submittedData();

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de horario no válido.';
            return;
        }

        $error = $this->validate($data);
        if ($error !== null) {
            $this->renderError($error, $data);
            return;
        }

        if ($this->schedules->instructorConflict(
            $data['IdCurso'],
            $data['Dia'],
            $data['HoraInicio'],
            $data['Hora_finalizacion'],
            $id
        )) {
            $this->renderError('El instructor ya tiene un curso asignado en ese horario.', $data);
            return;
        }

        if (!$this->schedules->update($id, $data)) {
            http_response_code(404);
            echo 'El horario solicitado no existe.';
            return;
        }

        $this->redirect('/horarios?mensaje=actualizado');
    }

    /** Elimina el horario indicado después de validar su identificador. */
    public function delete(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdHorario'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de horario no válido.';
            return;
        }
        if (!$this->schedules->delete($id)) {
            http_response_code(404);
            echo 'El horario solicitado no existe.';
            return;
        }

        $this->redirect('/horarios?mensaje=eliminado');
    }

    /** Obtiene y normaliza curso, día y horas enviados por el formulario. */
    private function submittedData(): array
    {
        return [
            'IdCurso' => filter_var($_POST['IdCurso'] ?? null, FILTER_VALIDATE_INT),
            'Dia' => (string) ($_POST['Dia'] ?? ''),
            'HoraInicio' => (string) ($_POST['HoraInicio'] ?? ''),
            'Hora_finalizacion' => (string) ($_POST['Hora_finalizacion'] ?? ''),
        ];
    }

    /**
     * Valida curso, día y formato y orden de las horas del horario.
     *
     * @param array<string, mixed> $data Datos del horario enviados por el formulario.
     * @return string|null Mensaje de validación o null si los datos son válidos.
     */
    private function validate(array &$data): ?string
    {
        if ($data['IdCurso'] === false || $data['IdCurso'] === null || $data['IdCurso'] < 1) {
            return 'Selecciona un curso válido.';
        }
        if (!in_array($data['Dia'], ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'], true)) {
            return 'Selecciona un día válido.';
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $data['HoraInicio'])
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $data['Hora_finalizacion'])) {
            return 'Ingresa horas válidas.';
        }
        if ($data['Hora_finalizacion'] <= $data['HoraInicio']) {
            return 'La hora de finalización debe ser posterior a la hora de inicio.';
        }

        return null;
    }

    /** Obtiene horarios, cursos y mensajes para la pantalla administrativa. */
    private function viewData(): array
    {
        $messages = [
            'creado' => 'Horario creado correctamente.',
            'actualizado' => 'Horario actualizado correctamente.',
            'eliminado' => 'Horario eliminado correctamente.',
        ];

        return [
            'title' => 'Horarios',
            'schedules' => $this->schedules->all(),
            'courses' => $this->schedules->courses(),
            'csrfToken' => $this->csrfToken(),
            'message' => $messages[(string) ($_GET['mensaje'] ?? '')] ?? null,
            'error' => null,
            'old' => [],
        ];
    }

    /** Renderiza la gestión de horarios y los datos introducidos. */
    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('horarios', $data);
    }
}
