<?php

declare(strict_types=1);

/**
 * Valida las solicitudes administrativas y prepara la vista de gestion de cursos.
 */
final class CursoController extends BaseController
{
    private Curso $courses;

    /** Prepara el modelo de cursos con la conexión PDO de la aplicación. */
    public function __construct()
    {
        $this->courses = new Curso(getDatabaseConnection());
    }

    /** Muestra cursos, categorías e instructores disponibles en la gestión administrativa. */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('cursos', $this->viewData());
    }

    /** Valida y crea un curso asociado a la categoría y al instructor elegidos. */
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

    /** Valida y actualiza los datos y el estado del curso seleccionado. */
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

    /** Cambia el estado de un curso existente a uno de los estados permitidos. */
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

    /** Normaliza y tipa los valores recibidos del formulario de cursos. */
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

    /**
     * Valida los campos del curso y normaliza sus valores para persistencia.
     *
     * @param array<string, mixed> $data Datos enviados por el formulario.
     * @return string|null Mensaje de validación o null si los datos son válidos.
     */
    private function validate(array &$data): ?string
    {
        if ($data['id_categoria'] === false || $data['id_categoria'] === null || $data['id_categoria'] < 1
            || $data['id_instructor'] === false || $data['id_instructor'] === null || $data['id_instructor'] < 1) {
            return 'Selecciona una categoría y un instructor válidos.';
        }
        if ($data['nombre_curso'] === '' || $data['duracion'] === '') {
            return 'El nombre y la duración son obligatorios.';
        }
        // Validar nombre del curso
        if (preg_match('/\p{L}/u', $data['nombre_curso']) !== 1) {
           return 'El nombre del curso debe contener al menos una letra.';
        }

        // Validar duración
        if (!preg_match('/^[1-9]\d*(?:\s+horas?)?$/iu', $data['duracion'])) {
            return 'La duración debe ser mayor que cero. Ejemplo: 50 o 50 horas.';
        }

        // Validar descripción (opcional)
        if ($data['descripcion'] !== '' && preg_match('/\p{L}/u', $data['descripcion']) !== 1) {
           return 'La descripción debe contener al menos una letra.';
        }

        // Validar requisitos (opcional)
        if ($data['requisitos'] !== '' && preg_match('/\p{L}/u', $data['requisitos']) !== 1) {
           return 'Los requisitos deben contener al menos una letra.';
        }

        if (mb_strlen($data['nombre_curso'], 'UTF-8') > 200 || mb_strlen($data['duracion'], 'UTF-8') > 100) {
            return 'El nombre o la duración exceden la longitud permitida.';
        }
        if (!in_array($data['modalidad'], ['Presencial', 'Virtual', 'Hibrido'], true)) {
            return 'Selecciona una modalidad válida.';
        }
        if (
            preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/', $data['costo_curso']) !== 1
            || (float) $data['costo_curso'] > 99999999.99
        ) {
            return 'El costo debe ser de 0 a 99,999,999.99 y puede tener hasta dos decimales.';
        }
        if (
            $data['maximo_cupos'] === false
            || $data['maximo_cupos'] === null
            || $data['maximo_cupos'] < 1
            || $data['maximo_cupos'] > 4294967295
        ) {
            return 'La capacidad debe ser un número entero entre 1 y 4,294,967,295.';
        }

        $data['costo_curso'] = number_format((float) $data['costo_curso'], 2, '.', '');
        $data['descripcion'] = $data['descripcion'] === '' ? null : $data['descripcion'];
        $data['requisitos'] = $data['requisitos'] === '' ? null : $data['requisitos'];
        $data['estado'] = 'Activo';

        return null;
    }

    /** Obtiene los datos comunes necesarios para renderizar el módulo de cursos. */
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

    /** Renderiza el módulo de cursos con el error y los datos ingresados. */
    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('cursos', $data);
    }
}
