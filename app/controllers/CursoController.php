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

        $data['IdAdmin'] = (int) $_SESSION['user']['administrator_id'];
        $data['Estado'] = 'Activo';
        $this->courses->create($data);
        $this->redirect('/cursos?mensaje=creado');
    }

    /** Valida y actualiza los datos y el Estado del curso seleccionado. */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdCurso'] ?? null, FILTER_VALIDATE_INT);
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

        $data['Estado'] = (string) ($_POST['Estado'] ?? '');
        if (!in_array($data['Estado'], ['Activo', 'Cerrado', 'Lleno', 'Cancelado'], true)) {
            $this->renderError('Selecciona un Estado válido.', $data);
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

    /** Cambia el Estado de un curso existente a uno de los estados permitidos. */
    public function setStatus(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdCurso'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['Estado'] ?? '');

        if ($id === false || $id === null || $id < 1
            || !in_array($status, ['Activo', 'Cerrado', 'Lleno', 'Cancelado'], true)) {
            http_response_code(400);
            echo 'Los datos enviados para el Estado del curso no son válidos.';
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
            'IdCategoria' => filter_var($_POST['IdCategoria'] ?? null, FILTER_VALIDATE_INT),
            'IdInstructor' => filter_var($_POST['IdInstructor'] ?? null, FILTER_VALIDATE_INT),
            'Nombre_Curso' => trim((string) ($_POST['Nombre_Curso'] ?? '')),
            'Descripcion' => trim((string) ($_POST['Descripcion'] ?? '')),
            'Duracion' => trim((string) ($_POST['Duracion'] ?? '')),
            'Modalidad' => (string) ($_POST['Modalidad'] ?? ''),
            'Requisitos' => trim((string) ($_POST['Requisitos'] ?? '')),
            'Costo_curso' => trim((string) ($_POST['Costo_curso'] ?? '')),
            'Maximo_cupos' => filter_var($_POST['Maximo_cupos'] ?? null, FILTER_VALIDATE_INT),
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
        if ($data['IdCategoria'] === false || $data['IdCategoria'] === null || $data['IdCategoria'] < 1
            || $data['IdInstructor'] === false || $data['IdInstructor'] === null || $data['IdInstructor'] < 1) {
            return 'Selecciona una categoría y un instructor válidos.';
        }
        if ($data['Nombre_Curso'] === '' || $data['Duracion'] === '') {
            return 'El Nombre y la duración son obligatorios.';
        }
        // Validar Nombre del curso
        if (preg_match('/\p{L}/u', $data['Nombre_Curso']) !== 1) {
           return 'El Nombre del curso debe contener texto.';
        }

        // Validar duración
        if (!preg_match('/^[1-9]\d*(?:\s+horas?)?$/iu', $data['Duracion'])) {
            return 'La duración debe ser mayor que cero. Ejemplo: 50 o 50 horas.';
        }

        // Validar descripción (opcional)
        if ($data['Descripcion'] !== '' && preg_match('/\p{L}/u', $data['Descripcion']) !== 1) {
           return 'La descripción debe contener  texto.';
        }

        // Validar Requisitos (opcional)
        if ($data['Requisitos'] !== '' && preg_match('/\p{L}/u', $data['Requisitos']) !== 1) {
           return 'Los Requisitos deben contener texto.';
        }

        if (mb_strlen($data['Nombre_Curso'], 'UTF-8') > 200 || mb_strlen($data['Duracion'], 'UTF-8') > 100) {
            return 'El Nombre o la duración exceden la longitud permitida.';
        }
        if (!in_array($data['Modalidad'], ['Presencial', 'Virtual', 'Hibrido'], true)) {
            return 'Selecciona una Modalidad válida.';
        }
        if (
            preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/', $data['Costo_curso']) !== 1
            || (float) $data['Costo_curso'] > 99999999.99
        ) {
            return 'El costo debe ser de 0 a 99,999,999.99 y puede tener hasta dos decimales.';
        }
        if (
            $data['Maximo_cupos'] === false
            || $data['Maximo_cupos'] === null
            || $data['Maximo_cupos'] < 1
            || $data['Maximo_cupos'] > 4294967295
        ) {
            return 'La capacidad debe ser un número entero entre 1 y 4,294,967,295.';
        }

        $data['Costo_curso'] = number_format((float) $data['Costo_curso'], 2, '.', '');
        $data['Descripcion'] = $data['Descripcion'] === '' ? null : $data['Descripcion'];
        $data['Requisitos'] = $data['Requisitos'] === '' ? null : $data['Requisitos'];
        $data['Estado'] = 'Activo';

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

    /** Renderizacion del módulo de cursos con el error y los datos ingresados. */
    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('cursos', $data);
    }
}
