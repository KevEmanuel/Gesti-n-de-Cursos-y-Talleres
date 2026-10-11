<?php

declare(strict_types=1);

/**
 * Valida y coordina la gestion administrativa de estudiantes y solicitudes de cuenta.
 */
final class EstudianteController extends BaseController
{
    private Estudiante $students;
    private SolicitudEstudiante $applications;

    public function __construct()
    {
        $database = getDatabaseConnection();
        $this->students = new Estudiante($database);
        $this->applications = new SolicitudEstudiante($database);
    }

    /**
     * Valida el acceso, obtiene los datos necesarios y muestra la vista del modulo.
     */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('estudiantes', $this->viewData());
    }

//funcion para crear un nuevo registro de estudiante 
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
            $this->students->create($data);
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage(), $data);
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('El correo ya está registrado o no se pudo reservar un carnet único.', $data);
                return;
            }
            throw $exception;
        }

        $this->redirect('/estudiantes?mensaje=creado');
    }
//funcion para actualizar un registro del estudiante 
    /**
     * Valida y guarda los cambios del elemento seleccionado.
     */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_estudiante'] ?? null, FILTER_VALIDATE_INT);
        $data = $this->submittedData(false);

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de estudiante no válido.';
            return;
        }

        $error = $this->validate($data, false);
        if ($error !== null) {
            $this->renderError($error);
            return;
        }

        try {
            if (!$this->students->update($id, $data)) {
                http_response_code(404);
                echo 'El estudiante solicitado no existe.';
                return;
            }
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('El correo ya pertenece a otro registro', $data);
                return;
            }
            throw $exception;
        }

        $this->redirect('/estudiantes?mensaje=actualizado');
    }

    //función que permite verificar el estado de la cuenta
    /**
     * Actualiza el estado del registro y aplica las reglas del modulo.
     */
    public function setStatus(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_estudiante'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['estado_cuenta'] ?? '');

        if ($id === false || $id === null || $id < 1 || !in_array($status, ['Activo', 'Inactivo'], true)) {
            http_response_code(400);
            echo 'Los datos enviados para el estado del estudiante no son válidos.';
            return;
        }

        if (!$this->students->setStatus($id, $status)) {
            http_response_code(404);
            echo 'El estudiante solicitado no existe.';
            return;
        }

        $this->redirect('/estudiantes?mensaje=estado_actualizado');
    }
//funcion para eliminar registros 
    /**
     * Valida y elimina el elemento seleccionado y sus relaciones permitidas.
     */
    public function delete(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_estudiante'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de estudiante no válido.';
            return;
        }

        try {
            if (!$this->students->delete($id)) {
                http_response_code(404);
                echo 'El estudiante solicitado no existe.';
                return;
            }
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage());
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('No se puede eliminar el estudiante porque tiene otros datos relacionados.');
                return;
            }
            throw $exception;
        }

        $this->redirect('/estudiantes?mensaje=eliminado');
    }

    //funcion para validar en envio de datos de solicitudes
    /**
     * Aprueba o rechaza una solicitud de cuenta estudiantil.
     */
    public function reviewApplication(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_solicitud_estudiante'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['estado_solicitud'] ?? '');

        if ($id === false || $id === null || $id < 1
            || !in_array($status, ['Aprobada', 'Rechazada'], true)) {
            http_response_code(400);
            echo 'Los datos enviados para revisar la solicitud no son válidos.';
            return;
        }

        try {
            if (!$this->applications->changeStatus(
                $id,
                $status,
                (int) $_SESSION['user']['administrator_id']
            )) {
                http_response_code(404);
                echo 'La solicitud de cuenta no existe.';
                return;
            }
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage());
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('No se puede aprobar: el correo ya pertenece a otra cuenta o no se pudo reservar un carnet único.');
                return;
            }
            throw $exception;
        }

        $this->redirect('/estudiantes?mensaje=' . ($status === 'Aprobada' ? 'solicitud_aprobada' : 'solicitud_rechazada'));
    }

    private function submittedData(bool $withPassword): array
    {
        $data = [
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'nombres' => trim((string) ($_POST['nombres'] ?? '')),
            'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'edad' => trim((string) ($_POST['edad'] ?? '')),
            'genero' => trim((string) ($_POST['genero'] ?? '')),
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
            || strlen($data['apellidos']) > 100 || strlen($data['telefono']) > 30) {
            return 'Uno o más campos exceden la longitud permitida.';
        }
        if ($data['edad'] !== '' && (!ctype_digit($data['edad']) || (int) $data['edad'] > 150)) {
            return 'La edad debe ser un número entre 0 y 150.';
        }
        if ($data['genero'] !== '' && !in_array($data['genero'], ['Masculino', 'Femenino', 'Otro'], true)) {
            return 'Selecciona un género válido.';
        }
        if ($withPassword && strlen($data['password']) < 12) {
            return 'La contraseña inicial debe tener al menos 12 caracteres.';
        }

        $data['telefono'] = $data['telefono'] === '' ? null : $data['telefono'];
        $data['edad'] = $data['edad'] === '' ? null : (int) $data['edad'];
        $data['genero'] = $data['genero'] === '' ? null : $data['genero'];

        return null;
    }

    private function viewData(): array
    {
        $messages = [
            'creado' => 'Estudiante creado. El carnet generado se muestra en la lista.',
            'actualizado' => 'Estudiante actualizado correctamente.',
            'estado_actualizado' => 'Estado del estudiante actualizado.',
            'eliminado' => 'Estudiante eliminado.',
            'solicitud_aprobada' => 'Solicitud aprobada. El carnet generado se muestra en la lista y en la cuenta del estudiante.',
            'solicitud_rechazada' => 'Solicitud rechazada.',
        ];

        return [
            'title' => 'Estudiantes',
            'students' => $this->students->all(),
            'applications' => $this->applications->all(),
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
        $this->render('estudiantes', $data);
    }
}
