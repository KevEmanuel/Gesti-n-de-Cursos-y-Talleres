<?php

declare(strict_types=1);

/**
 * Valida las solicitudes y coordina la gestion de horarios de cursos.
 */
final class HorarioController extends BaseController
{
    private Horario $schedules;

    public function __construct()
    {
        $this->schedules = new Horario(getDatabaseConnection());
    }

    /**
     * Valida el acceso, obtiene los datos necesarios y muestra la vista del modulo.
     */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('horarios', $this->viewData());
    }

    /**
     * Valida y registra un nuevo elemento del modulo.
     */
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
            $data['id_curso'],
            $data['dia'],
            $data['hora_inicio'],
            $data['hora_finalizacion']
        )) {
            $this->renderError('El instructor ya tiene un curso asignado en ese horario.', $data);
            return;
        }

        $this->schedules->create($data);
        $this->redirect('/horarios?mensaje=creado');
    }

    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_horario'] ?? null, FILTER_VALIDATE_INT);
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
            $data['id_curso'],
            $data['dia'],
            $data['hora_inicio'],
            $data['hora_finalizacion'],
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

    /**
     * Valida y elimina el elemento seleccionado y sus relaciones permitidas.
     */
    public function delete(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_horario'] ?? null, FILTER_VALIDATE_INT);

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

    private function submittedData(): array
    {
        return [
            'id_curso' => filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT),
            'dia' => (string) ($_POST['dia'] ?? ''),
            'hora_inicio' => (string) ($_POST['hora_inicio'] ?? ''),
            'hora_finalizacion' => (string) ($_POST['hora_finalizacion'] ?? ''),
        ];
    }

    private function validate(array &$data): ?string
    {
        if ($data['id_curso'] === false || $data['id_curso'] === null || $data['id_curso'] < 1) {
            return 'Selecciona un curso válido.';
        }
        if (!in_array($data['dia'], ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'], true)) {
            return 'Selecciona un día válido.';
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $data['hora_inicio'])
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $data['hora_finalizacion'])) {
            return 'Ingresa horas válidas.';
        }
        if ($data['hora_finalizacion'] <= $data['hora_inicio']) {
            return 'La hora de finalización debe ser posterior a la hora de inicio.';
        }

        return null;
    }

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

    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('horarios', $data);
    }
}
