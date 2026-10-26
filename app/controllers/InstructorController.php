<?php

declare(strict_types=1);

/**
 * Valida y coordina la gestion administrativa de instructores.
 */
final class InstructorController extends BaseController
{
    private Instructor $instructors;
    private Usuario $users;

    /** Prepara los modelos de instructores y usuarios con la misma conexión PDO. */
    public function __construct()
    {
        $database = getDatabaseConnection();
        $this->instructors = new Instructor($database);
        $this->users = new Usuario($database);
    }

    /** Muestra instructores, cursos asignados y acciones de administración. */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('instructores', $this->viewData());
    }

    /** Valida los datos y crea el perfil y la cuenta de un instructor. */
    public function create(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $data = $this->submittedData(true);
        $error = $this->validate($data, true);

        if ($error !== null) {
            $this->renderError($error, $data);
            return;
        }

        try {
            $this->instructors->create($data);
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage(), $data);
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('Ya existe un usuario con ese correo o no se pudo reservar un carnet único.', $data);
                return;
            }
            throw $exception;
        }

        $this->redirect('/instructores?mensaje=creado');
    }

    /** Actualiza los datos personales, especialidad y carnet del instructor. */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_instructor'] ?? null, FILTER_VALIDATE_INT);
        $data = $this->submittedData(false);

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de instructor no válido.';
            return;
        }

        $error = $this->validate($data, false);
        if ($error !== null) {
            $this->renderError($error);
            return;
        }

        try {
            if (!$this->instructors->update($id, $data)) {
                http_response_code(404);
                echo 'El instructor solicitado no existe.';
                return;
            }
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('Ese correo ya pertenece a otro usuario o no se pudo generar un carnet único.', $data);
                return;
            }
            throw $exception;
        }

        $this->redirect('/instructores?mensaje=actualizado');
    }

    /** Activa o desactiva la cuenta y reasigna cursos cuando sea necesario. */
    public function setStatus(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_instructor'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['estado_cuenta'] ?? '');
        $replacementValue = trim((string) ($_POST['id_instructor_reemplazo'] ?? ''));
        $replacementId = $replacementValue === ''
            ? null
            : filter_var($replacementValue, FILTER_VALIDATE_INT);

        if (
            $id === false || $id === null || $id < 1
            || !in_array($status, ['Activo', 'Inactivo'], true)
            || ($replacementValue !== '' && ($replacementId === false || $replacementId < 1))
        ) {
            http_response_code(400);
            echo 'Los datos enviados para cambiar el estado del instructor no son válidos.';
            return;
        }

        try {
            if (!$this->instructors->setStatus(
                $id,
                $status,
                $replacementId === false ? null : $replacementId
            )) {
                http_response_code(404);
                echo 'El instructor solicitado no existe.';
                return;
            }
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage());
            return;
        }

        $message = match (true) {
            $status === 'Activo' => 'activado',
            $replacementId !== null => 'cursos_reasignados',
            default => 'desactivado',
        };
        $this->redirect('/instructores?mensaje=' . $message);
    }

    /**
     * Asigna una nueva contraseña a la cuenta de un instructor.
     */
    public function setPassword(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_instructor'] ?? null, FILTER_VALIDATE_INT);
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de instructor no válido.';
            return;
        }

        if (mb_strlen($password, 'UTF-8') < 12 || $password !== $confirmation) {
            $this->renderError(
                $password !== $confirmation
                    ? 'Las contraseñas no coinciden.'
                    : 'La contraseña debe tener al menos 12 caracteres.'
            );
            return;
        }

        if (!$this->users->setManagedPassword('instructor', $id, password_hash($password, PASSWORD_DEFAULT))) {
            http_response_code(404);
            echo 'El instructor solicitado no existe.';
            return;
        }

        $this->redirect('/instructores?mensaje=contrasena_actualizada');
    }

    /** Elimina la cuenta del instructor y reasigna sus cursos si corresponde. */
    public function delete(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_instructor'] ?? null, FILTER_VALIDATE_INT);
        $replacementValue = trim((string) ($_POST['id_instructor_reemplazo'] ?? ''));
        $replacementId = $replacementValue === ''
            ? null
            : filter_var($replacementValue, FILTER_VALIDATE_INT);

        if ($id === false || $id === null || $id < 1
            || ($replacementValue !== '' && ($replacementId === false || $replacementId < 1))) {
            http_response_code(400);
            echo 'Los datos enviados para eliminar el instructor no son válidos.';
            return;
        }

        try {
            if (!$this->instructors->delete($id, $replacementId === false ? null : $replacementId)) {
                http_response_code(404);
                echo 'El instructor solicitado no existe.';
                return;
            }
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage());
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('No se puede eliminar el instructor porque aún tiene datos relacionados. Reasigna sus cursos y revisa sus dependencias.');
                return;
            }
            throw $exception;
        }

        $this->redirect('/instructores?mensaje=eliminado');
    }

    /** Normaliza los datos del formulario y añade la contraseña únicamente al crear. */
    private function submittedData(bool $withPassword): array
    {
        $data = [
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'nombres' => trim((string) ($_POST['nombres'] ?? '')),
            'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'especialidad' => trim((string) ($_POST['especialidad'] ?? '')),
        ];

        if ($withPassword) {
            $data['password'] = (string) ($_POST['password'] ?? '');
        }

        return $data;
    }

    /**
     * Valida datos personales, contacto, especialidad y la contraseña de alta.
     *
     * @param array<string, mixed> $data Datos del instructor enviados por el formulario.
     * @return string|null Mensaje de validación o null si los datos son válidos.
     */
    private function validate(array &$data, bool $withPassword): ?string
    {
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un correo electrónico válido.';
        }
        // Validar nombres
        if (preg_match("/^\p{L}+(?:[ '-]\p{L}+)*$/u", $data['nombres']) !== 1) {
            return 'El nombre solo puede contener letras, espacios, guiones y apóstrofes.';
        }

        // Validar apellidos
        if (preg_match("/^\p{L}+(?:[ '-]\p{L}+)*$/u", $data['apellidos']) !== 1) {
           return 'El apellido solo puede contener letras, espacios, guiones y apóstrofes.';
        }

        // Validar especialidad (opcional)
        if ($data['especialidad'] !== '' && preg_match('/\p{L}/u', $data['especialidad']) !== 1) {
           return 'La especialidad debe contener al menos una letra.';
        }

        // Validar número de celular salvadoreño
        if ($data['telefono'] !== '' && preg_match('/^[567][0-9]{7}$/', $data['telefono']) !== 1) {
           return 'El celular debe tener 8 dígitos y comenzar con 5, 6 o 7.';
        }

        if (mb_strlen($data['correo'], 'UTF-8') > 254 || mb_strlen($data['nombres'], 'UTF-8') > 100
            || mb_strlen($data['apellidos'], 'UTF-8') > 100 || mb_strlen($data['telefono'], 'UTF-8') > 30
            || mb_strlen($data['especialidad'], 'UTF-8') > 255) {
            return 'Uno o más campos exceden la longitud permitida.';
        }
        if ($withPassword && mb_strlen($data['password'], 'UTF-8') < 12) {
            return 'La contraseña inicial debe tener al menos 12 caracteres.';
        }

        $data['telefono'] = $data['telefono'] === '' ? null : $data['telefono'];
        $data['especialidad'] = $data['especialidad'] === '' ? null : $data['especialidad'];

        return null;
    }

    /** Obtiene instructores, cursos asignados y mensajes para la vista administrativa. */
    private function viewData(): array
    {
        $messages = [
            'creado' => 'Instructor creado. El carnet generado se muestra en la lista.',
            'actualizado' => 'Instructor actualizado correctamente.',
            'contrasena_actualizada' => 'Contraseña actualizada. Comparte la nueva contraseña con el instructor por un canal seguro.',
            'activado' => 'Cuenta de instructor activada.',
            'desactivado' => 'Cuenta de instructor desactivada.',
            'cursos_reasignados' => 'Cursos reasignados a otro instructor; la cuenta anterior permanece inactiva.',
            'eliminado' => 'Instructor eliminado y sus cursos reasignados.',
        ];

        return [
            'title' => 'Instructores',
            'instructors' => $this->instructors->all(),
            'csrfToken' => $this->csrfToken(),
            'error' => null,
            'old' => [],
            'message' => $messages[(string) ($_GET['mensaje'] ?? '')] ?? null,
        ];
    }

    /** Renderiza la gestión de instructores con el error y los datos no sensibles. */
    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('instructores', $data);
    }
}
