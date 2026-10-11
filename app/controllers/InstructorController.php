<?php

declare(strict_types=1);

/**
 * Valida y coordina la gestion administrativa de instructores.
 */
final class InstructorController extends BaseController
{
    private Instructor $instructors;

    public function __construct()
    {
        $this->instructors = new Instructor(getDatabaseConnection());
    }

    /**
     * Valida el acceso, obtiene los datos necesarios y muestra la vista del modulo.
     */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('instructores', $this->viewData());
    }

    /**
     * Valida y registra un nuevo elemento del modulo.
     */
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

    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
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

    /**
     * Actualiza el estado del registro y aplica las reglas del modulo.
     */
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
     * Valida y elimina el elemento seleccionado y sus relaciones permitidas.
     */
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

    private function validate(array &$data, bool $withPassword): ?string
    {
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un correo electrónico válido.';
        }
        if ($data['nombres'] === '' || preg_match('/\p{L}/u', $data['apellidos']) !== 1) {
            return 'Ingresa un nombre y un apellido que contenga letras.';
        }
        if (strlen($data['correo']) > 254 || strlen($data['nombres']) > 100
            || strlen($data['apellidos']) > 100 || strlen($data['telefono']) > 30
            || strlen($data['especialidad']) > 255) {
            return 'Uno o más campos exceden la longitud permitida.';
        }
        if ($withPassword && strlen($data['password']) < 12) {
            return 'La contraseña inicial debe tener al menos 12 caracteres.';
        }

        $data['telefono'] = $data['telefono'] === '' ? null : $data['telefono'];
        $data['especialidad'] = $data['especialidad'] === '' ? null : $data['especialidad'];

        return null;
    }

    private function viewData(): array
    {
        $messages = [
            'creado' => 'Instructor creado. El carnet generado se muestra en la lista.',
            'actualizado' => 'Instructor actualizado correctamente.',
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

    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('instructores', $data);
    }
}
