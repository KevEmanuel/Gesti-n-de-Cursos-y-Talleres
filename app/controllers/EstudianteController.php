<?php

declare(strict_types=1);

/**
 * Valida y coordina la gestion administrativa de estudiantes y solicitudes de cuenta.
 */
final class EstudianteController extends BaseController
{
    private Estudiante $students;
    private SolicitudEstudiante $applications;
    private Usuario $users;

    /** Prepara los modelos de estudiantes, solicitudes y usuarios con una conexión común. */
    public function __construct()
    {
        $database = getDatabaseConnection();
        $this->students = new Estudiante($database);
        $this->applications = new SolicitudEstudiante($database);
        $this->users = new Usuario($database);
    }

    /** Muestra las solicitudes de cuenta y los perfiles estudiantiles al administrador. */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('estudiantes', $this->viewData());
    }

    /**
     * Valida los datos personales y la contraseña inicial y registra un estudiante.
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
                $this->renderError('El Correo ya está registrado o no se pudo reservar un Carnet único.', $data);
                return;
            }
            throw $exception;
        }

        $this->redirect('/estudiantes?mensaje=creado');
    }
    /**
     * Actualiza los datos personales y el Carnet del estudiante seleccionado.
     */
    public function update(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdEstudiante'] ?? null, FILTER_VALIDATE_INT);
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
                $this->renderError('El Correo ya pertenece a otro registro', $data);
                return;
            }
            throw $exception;
        }

        $this->redirect('/estudiantes?mensaje=actualizado');
    }

    /**
     * Activa o desactiva la cuenta del estudiante seleccionado.
     */
    public function setStatus(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdEstudiante'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['Estado_Cuenta'] ?? '');

        if ($id === false || $id === null || $id < 1 || !in_array($status, ['Activo', 'Inactivo'], true)) {
            http_response_code(400);
            echo 'Los datos enviados para el Estado del estudiante no son válidos.';
            return;
        }

        if (!$this->students->setStatus($id, $status)) {
            http_response_code(404);
            echo 'El estudiante solicitado no existe.';
            return;
        }

        $this->redirect('/estudiantes?mensaje=estado_actualizado');
    }

    /**
     * Asigna una nueva contraseña a la cuenta de un estudiante.
     */
    public function setPassword(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdEstudiante'] ?? null, FILTER_VALIDATE_INT);
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            echo 'Identificador de estudiante no válido.';
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

        if (!$this->users->setManagedPassword('student', $id, password_hash($password, PASSWORD_DEFAULT))) {
            http_response_code(404);
            echo 'El estudiante solicitado no existe.';
            return;
        }

        $this->redirect('/estudiantes?mensaje=contrasena_actualizada');
    }
    /**
     * Elimina un estudiante cuando sus relaciones permiten hacerlo.
     */
    public function delete(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdEstudiante'] ?? null, FILTER_VALIDATE_INT);

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

    /**
     * Aprueba o rechaza una solicitud de cuenta estudiantil.
     */
    public function reviewApplication(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['IdSolicitudEstudiante'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['EstadoSolicitud'] ?? '');

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
                $this->renderError('No se puede aprobar: el Correo ya pertenece a otra cuenta o no se pudo reservar un Carnet único.');
                return;
            }
            throw $exception;
        }

        $this->redirect('/estudiantes?mensaje=' . ($status === 'Aprobada' ? 'solicitud_aprobada' : 'solicitud_rechazada'));
    }

    /** Normaliza los campos del formulario y agrega la contraseña solo durante el alta. */
    private function submittedData(bool $withPassword): array
    {
        $data = [
            'Correo' => trim((string) ($_POST['Correo'] ?? '')),
            'Nombres' => trim((string) ($_POST['Nombres'] ?? '')),
            'Apellidos' => trim((string) ($_POST['Apellidos'] ?? '')),
            'Telefono' => trim((string) ($_POST['Telefono'] ?? '')),
            'Edad' => trim((string) ($_POST['Edad'] ?? '')),
            'Genero' => trim((string) ($_POST['Genero'] ?? '')),
        ];

        if ($withPassword) {
            $data['password'] = (string) ($_POST['password'] ?? '');
        }

        return $data;
    }

    /**
     * Valida campos y reglas del estudiante y normaliza valores vacíos para la base.
     *
     * @param array<string, mixed> $data Datos enviados por el formulario.
     * @return string|null Mensaje de validación o null si los datos son válidos.
     */
    private function validate(array &$data, bool $withPassword): ?string
    {
        if (!filter_var($data['Correo'], FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un Correo electrónico válido.';
        }
        // Validar que los Nombres contengan únicamente letras,
        // espacios, guiones y apóstrofes.
        if (preg_match("/^\p{L}+(?:[ '-]\p{L}+)*$/u", $data['Nombres']) !== 1) {
            return 'El Nombre solo puede contener letras, espacios, guiones y apóstrofes.';
        }

         // Validar que los Apellidos no contengan números ni símbolos inválidos.
         if (preg_match("/^\p{L}+(?:[ '-]\p{L}+)*$/u", $data['Apellidos']) !== 1) {
            return 'El apellido solo puede contener letras, espacios, guiones y apóstrofes.';
        }

        if (mb_strlen($data['Correo'], 'UTF-8') > 254 || mb_strlen($data['Nombres'], 'UTF-8') > 100
            || mb_strlen($data['Apellidos'], 'UTF-8') > 100 || mb_strlen($data['Telefono'], 'UTF-8') > 30) {
            return 'Uno o más campos exceden la longitud permitida.';
        }
        if (!ctype_digit($data['Edad']) || (int) $data['Edad'] < 17 || (int) $data['Edad'] > 70) {
            return 'La Edad es obligatoria y debe estar entre 17 y 70 años.';
        }
        if ($data['Genero'] !== '' && !in_array($data['Genero'], ['Masculino', 'Femenino', 'Otro'], true)) {
            return 'Selecciona un género válido.';
        }
        if ($withPassword && mb_strlen($data['password'], 'UTF-8') < 12) {
            return 'La contraseña inicial debe tener al menos 12 caracteres.';
        }

        // Validar que el celular tenga exactamente 8 dígitos
        // y comience con 5, 6 o 7.
        if ($data['Telefono'] !== '' && preg_match('/^[567][0-9]{7}$/', $data['Telefono']) !== 1) {
            return 'El celular debe tener 8 dígitos y comenzar con 5, 6 o 7.';
        }

        $data['Telefono'] = $data['Telefono'] === '' ? null : $data['Telefono'];
        $data['Edad'] = (int) $data['Edad'];
        $data['Genero'] = $data['Genero'] === '' ? null : $data['Genero'];

        return null;
    }

    /** Prepara estudiantes, solicitudes y mensajes para la vista administrativa. */
    private function viewData(): array
    {
        $messages = [
            'creado' => 'Estudiante creado. El Carnet generado se muestra en la lista.',
            'actualizado' => 'Estudiante actualizado correctamente.',
            'estado_actualizado' => 'Estado del estudiante actualizado.',
            'contrasena_actualizada' => 'Contraseña actualizada. Comparte la nueva contraseña con el estudiante por un canal seguro.',
            'eliminado' => 'Estudiante eliminado.',
            'solicitud_aprobada' => 'Solicitud aprobada. El Carnet generado se muestra en la lista y en la cuenta del estudiante.',
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

    /** Vuelve a mostrar la gestión de estudiantes con el error y datos no sensibles. */
    private function renderError(string $error, array $old = []): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['old'] = $old;
        $this->render('estudiantes', $data);
    }
}
