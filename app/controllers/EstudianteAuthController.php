<?php

declare(strict_types=1);

/**
 * Coordina las solicitudes publicas de registro de estudiantes.
 */
final class EstudianteAuthController extends BaseController
{
    private SolicitudEstudiante $applications;

    public function __construct()
    {
        $this->applications = new SolicitudEstudiante(getDatabaseConnection());
    }

    /**
     * Muestra el formulario para solicitar una cuenta estudiantil.
     */
    public function register(): void
    {
        $this->render('estudiante/registro', [
            'title' => 'Solicitar cuenta de estudiante',
            'csrfToken' => $this->csrfToken(),
            'error' => null,
            'message' => null,
            'old' => [],
        ]);
    }

    /**
     * Valida y registra la solicitud de cuenta estudiantil.
     */
    public function submitApplication(): void
    {
        $this->verifyCsrfToken();
        $data = [
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'nombres' => trim((string) ($_POST['nombres'] ?? '')),
            'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'edad' => trim((string) ($_POST['edad'] ?? '')),
            'genero' => trim((string) ($_POST['genero'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];
        $error = $this->validateRegistration($data);

        if ($error !== null) {
            $this->renderRegistrationError($error, $data);
            return;
        }

        $data['edad'] = $data['edad'] === '' ? null : (int) $data['edad'];
        $data['telefono'] = $data['telefono'] === '' ? null : $data['telefono'];
        $data['genero'] = $data['genero'] === '' ? null : $data['genero'];

        try {
            $this->applications->create($data);
        } catch (DomainException $exception) {
            $this->renderRegistrationError($exception->getMessage(), $data);
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderRegistrationError('Ya existe una cuenta o solicitud pendiente con ese correo.', $data);
                return;
            }
            throw $exception;
        }

        $this->redirect('/login?registro=solicitud_enviada');
    }

    private function validateRegistration(array $data): ?string
    {
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un correo electrónico válido.';
        }
        if ($data['nombres'] === '' || preg_match('/\p{L}/u', $data['apellidos']) !== 1) {
            return 'Ingresa nombres y un apellido que contenga letras.';
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
        if (strlen($data['password']) < 12) {
            return 'La contraseña debe tener al menos 12 caracteres.';
        }
        if ($data['password'] !== $data['password_confirmation']) {
            return 'Las contraseñas no coinciden.';
        }

        return null;
    }

    private function renderRegistrationError(string $error, array $data): void
    {
        http_response_code(422);
        unset($data['password'], $data['password_confirmation']);
        $this->render('estudiante/registro', [
            'title' => 'Solicitar cuenta de estudiante',
            'csrfToken' => $this->csrfToken(),
            'error' => $error,
            'message' => null,
            'old' => $data,
        ]);
    }
}
