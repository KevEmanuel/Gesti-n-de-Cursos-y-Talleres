<?php

declare(strict_types=1);

/**
 * Valida las solicitudes administrativas y prepara la vista de gestion de cursos.
 */
final class CursoController extends BaseController
{
    private Curso $courses;

    public function __construct()
    {
        $this->courses = new Curso(getDatabaseConnection());
    }

    /**
     * Valida el acceso, obtiene los datos necesarios y muestra la vista del modulo.
     */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('cursos', $this->viewData());
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

        $data['id_admin'] = (int) $_SESSION['user']['administrator_id'];
        $data['estado'] = 'Activo';
        $this->courses->create($data);
        $this->redirect('/cursos?mensaje=creado');
    }

    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT);
        $data = $this->submittedData();

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de curso no válido.';
            return;
        }

        $error = $this->validate($data);
        if ($error !== null) {
            $this->renderError($error, $data);
            return;
        }

        $data['estado'] = (string) ($_POST['estado'] ?? '');
        if (!in_array($data['estado'], ['Activo', 'Cerrado', 'Lleno', 'Cancelado'], true)) {
            $this->renderError('Selecciona un estado válido.', $data);
            return;
        }

        try {
            if (!$this->courses->update($id, $data)) {
                http_response_code(404);
                echo 'El curso solicitado no existe.';
                return;
            }
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage(), $data);
            return;
        }

        $this->redirect('/cursos?mensaje=actualizado');
    }

    /**
     * Actualiza el estado del registro y aplica las reglas del modulo.
     */
    public function setStatus(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['estado'] ?? '');

        if ($id === false || $id === null || $id < 1
            || !in_array($status, ['Activo', 'Cerrado', 'Lleno', 'Cancelado'], true)) {
            http_response_code(400);
            echo 'Los datos enviados para el estado del curso no son válidos.';
            return;
        }

        if (!$this->courses->setStatus($id, $status)) {
            http_response_code(404);
            echo 'El curso solicitado no existe.';
            return;
        }

        $this->redirect('/cursos?mensaje=estado_actualizado');
    }

    private function submittedData(): array
    {
        return [
            'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT),
            'id_instructor' => filter_var($_POST['id_instructor'] ?? null, FILTER_VALIDATE_INT),
            'nombre_curso' => trim((string) ($_POST['nombre_curso'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'duracion' => trim((string) ($_POST['duracion'] ?? '')),
            'modalidad' => (string) ($_POST['modalidad'] ?? ''),
            'requisitos' => trim((string) ($_POST['requisitos'] ?? '')),
            'costo_curso' => trim((string) ($_POST['costo_curso'] ?? '')),
            'maximo_cupos' => filter_var($_POST['maximo_cupos'] ?? null, FILTER_VALIDATE_INT),
        ];
    }

    private function validate(array &$data): ?string
    {
        if ($data['id_categoria'] === false || $data['id_categoria'] === null || $data['id_categoria'] < 1
            || $data['id_instructor'] === false || $data['id_instructor'] === null || $data['id_instructor'] < 1) {
            return 'Selecciona una categoría y un instructor válidos.';
        }
        if ($data['nombre_curso'] === '' || $data['duracion'] === '') {
            return 'El nombre y la duración son obligatorios.';
        }
        if (strlen($data['nombre_curso']) > 200 || strlen($data['duracion']) > 100) {
            return 'El nombre o la duración exceden la longitud permitida.';
        }
        if (!in_array($data['modalidad'], ['Presencial', 'Virtual', 'Hibrido'], true)) {
            return 'Selecciona una modalidad válida.';
        }
        if (!is_numeric($data['costo_curso']) || (float) $data['costo_curso'] < 0) {
            return 'El costo debe ser un número igual o mayor que cero.';
        }
        if ($data['maximo_cupos'] === false || $data['maximo_cupos'] === null || $data['maximo_cupos'] < 1) {
            return 'La capacidad debe ser un número mayor que cero.';
        }

        $data['costo_curso'] = number_format((float) $data['costo_curso'], 2, '.', '');
        $data['descripcion'] = $data['descripcion'] === '' ? null : $data['descripcion'];
        $data['requisitos'] = $data['requisitos'] === '' ? null : $data['requisitos'];
        $data['estado'] = 'Activo';

        return null;
    }

    private function viewData(): array
    {
        $messages = [
            'creado' => 'Curso creado correctamente.',
            'actualizado' => 'Curso actualizado correctamente.',
            'estado_actualizado' => 'Estado del curso actualizado.',
        ];

        return [
            'title' => 'Cursos',
            'courses' => $this->courses->all(),
            'categories' => $this->courses->categories(),
            'instructors' => $this->courses->instructors(),
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
        $this->render('cursos', $data);
    }
}
