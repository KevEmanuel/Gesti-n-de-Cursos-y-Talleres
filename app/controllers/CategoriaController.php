<?php

declare(strict_types=1);

/**
 * Valida las solicitudes y coordina las operaciones administrativas de categorias.
 */
final class CategoriaController extends BaseController
{
    private Categoria $categories;

    /** Prepara el modelo de categorías con la conexión PDO de la aplicación. */
    public function __construct()
    {
        $this->categories = new Categoria(getDatabaseConnection());
    }

    /** Muestra las categorías y el formulario de gestión al administrador. */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('categorias', [
            'title' => 'Categorías',
            'categories' => $this->categories->all(),
            'csrfToken' => $this->csrfToken(),
            'message' => $this->message(),
            'error' => null,
            'old' => [],
        ]);
    }

    /** Valida los datos del formulario y crea una categoría nueva. */
    public function create(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();

        $name = trim((string) ($_POST['nombre'] ?? ''));
        $description = trim((string) ($_POST['descripcion'] ?? ''));
        $error = $this->validate($name, $description);

        if ($error !== null) {
            $this->renderFormError($error, ['nombre' => $name, 'descripcion' => $description]);
            return;
        }

        $this->categories->create($name, $description === '' ? null : $description);
        $this->redirect('/categorias?mensaje=creada');
    }

    /** Valida y guarda el nombre, descripción y estado de la categoría seleccionada. */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();

        $id = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
        $name = trim((string) ($_POST['nombre'] ?? ''));
        $description = trim((string) ($_POST['descripcion'] ?? ''));
        $status = (string) ($_POST['estado'] ?? '');

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de categoría no válido.';
            return;
        }

        $error = $this->validate($name, $description, $id);
        if ($error === null && !in_array($status, ['Activo', 'Inactivo'], true)) {
            $error = 'Selecciona un estado válido.';
        }

        if ($error !== null) {
            $this->renderFormError($error);
            return;
        }

        if (!$this->categories->update($id, $name, $description === '' ? null : $description, $status)) {
            http_response_code(404);
            echo 'La categoría solicitada no existe.';
            return;
        }

        $this->redirect('/categorias?mensaje=actualizada');
    }

    /** Activa o desactiva una categoría existente. */
    public function setStatus(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();

        $id = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['estado'] ?? '');

        if ($id === false || $id === null || $id < 1 || !in_array($status, ['Activo', 'Inactivo'], true)) {
            http_response_code(400);
            echo 'Los datos enviados para cambiar el estado no son válidos.';
            return;
        }

        if (!$this->categories->setStatus($id, $status)) {
            http_response_code(404);
            echo 'La categoría solicitada no existe.';
            return;
        }

        $message = $status === 'Activo' ? 'activada' : 'desactivada';
        $this->redirect('/categorias?mensaje=' . $message);
    }

    /**
     * Valida el nombre y la descripción, incluida la unicidad del nombre.
     *
     * @return string|null Mensaje de error o null si los datos son válidos.
     */
    private function validate(string $name, string $description, ?int $excludedId = null): ?string
    {
        // VALIDACIÓN AGREGADA: Comprueba que el nombre de la categoría
        // contenga al menos una letra, evitando nombres solo numéricos o símbolos.
        if (preg_match('/\p{L}/u', $name) !== 1) {
            return 'El nombre debe contener al menos una letra.';
        }

        // VALIDACIÓN AGREGADA: Comprueba que la descripción contenga al menos
        // una letra si el usuario la proporciona. Permite dejarla vacía.
        if ($description !== '' &&
            preg_match('/\p{L}/u', $description) !== 1) {
            return 'La descripción debe contener texto válido.';
        }
        if ($name === '') {
            return 'El nombre de la categoría es obligatorio.';
        }

        if (mb_strlen($name, 'UTF-8') > 150 || mb_strlen($description, 'UTF-8') > 500) {
            return 'El nombre admite hasta 150 caracteres y la descripción hasta 500.';
        }

        if ($this->categories->nameExists($name, $excludedId)) {
            return 'Ya existe una categoría con ese nombre.';
        }

        return null;
    }

    /** Vuelve a mostrar la gestión de categorías con el error y los datos ingresados. */
    private function renderFormError(string $error, array $old = []): void
    {
        http_response_code(422);
        $this->render('categorias', [
            'title' => 'Categorías',
            'categories' => $this->categories->all(),
            'csrfToken' => $this->csrfToken(),
            'message' => null,
            'error' => $error,
            'old' => $old,
        ]);
    }

    /** Resuelve el mensaje visual asociado al resultado de la última operación. */
    private function message(): ?string
    {
        $messages = [
            'creada' => 'La categoría se creó correctamente.',
            'actualizada' => 'La categoría se actualizó correctamente.',
            'activada' => 'La categoría se activó correctamente.',
            'desactivada' => 'La categoría se desactivó correctamente.',
        ];

        $key = (string) ($_GET['mensaje'] ?? '');

        return $messages[$key] ?? null;
    }
}
