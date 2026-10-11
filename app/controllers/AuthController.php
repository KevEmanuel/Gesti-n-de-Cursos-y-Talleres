<?php

declare(strict_types=1);

/**
 * Gestiona el acceso comun, la autenticacion por rol y el cierre de sesion.
 */
final class AuthController extends BaseController
{
    private Usuario $users;

    public function __construct()
    {
        $this->users = new Usuario(getDatabaseConnection());
    }

    /**
     * Muestra el formulario de creacion del administrador inicial.
     */
    public function setup(): void
    {
        if ($this->users->hasAdministrator()) {
            $this->redirect('/login');
        }

        $this->render('auth/setup', [
            'title' => 'Configuración inicial del administrador',
            'csrfToken' => $this->csrfToken(),
            'error' => null,
            'old' => [],
        ]);
    }

    /**
     * Valida y crea la primera cuenta administradora.
     */
    public function createFirstAdministrator(): void
    {
        $this->verifyCsrfToken();

        if ($this->users->hasAdministrator()) {
            $this->redirect('/login');
        }

        $email = trim((string) ($_POST['correo'] ?? ''));
        $firstName = trim((string) ($_POST['nombres'] ?? ''));
        $lastName = trim((string) ($_POST['apellidos'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        $error = $this->validateAdministrator($email, $firstName, $lastName, $password, $passwordConfirmation);

        if ($error !== null) {
            $this->renderSetupError($error, [
                'correo' => $email,
                'nombres' => $firstName,
                'apellidos' => $lastName,
            ]);
            return;
        }

        try {
            $this->users->createFirstAdministrator($email, $password, $firstName, $lastName);
        } catch (DomainException) {
            $this->redirect('/login');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderSetupError('Ya existe una cuenta con ese correo.', [
                    'correo' => $email,
                    'nombres' => $firstName,
                    'apellidos' => $lastName,
                ]);
                return;
            }

            throw $exception;
        }

        $this->redirect('/login?mensaje=administrador_creado');
    }

    /**
     * login de inicio de sesion.
     */
    public function login(): void
    {
        if (!$this->users->hasAdministrator()) {
            $this->redirect('/setup');
        }

        $messages = [
            'administrador_creado' => 'Administrador inicial creado. Ya puedes iniciar sesión.',
            'solicitud_enviada' => 'Tu solicitud fue enviada. Podrás iniciar sesión cuando el administrador la apruebe.',
        ];
        $this->render('auth/login', [
            'title' => 'Iniciar sesión | Curso Taller',
            'csrfToken' => $this->csrfToken(),
            'error' => null,
            'message' => $messages[(string) ($_GET['mensaje'] ?? $_GET['registro'] ?? '')] ?? null,
            'old' => [],
        ]);
    }

    /**
     * Redirige una ruta de acceso antigua al formulario comun.
     */
    public function redirectLegacyLogin(): void
    {
        $query = (string) ($_GET['registro'] ?? '') === 'solicitud_enviada'
            ? '?registro=solicitud_enviada'
            : '';
        $this->redirect('/login' . $query);
    }

    /**
     * Verifica las credenciales, identifica el rol y abre la sesion correspondiente.
     */
    public function authenticate(): void
    {
        $this->verifyCsrfToken();

        $email = trim((string) ($_POST['correo'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = $this->users->byCredentials($email);

        if (
            $user === null
            || $user['estado_cuenta'] !== 'Activo'
            || !password_verify($password, (string) $user['password_hash'])
        ) {
            http_response_code(422);
            $this->render('auth/login', [
                'title' => 'Iniciar sesión | Curso Taller',
                'csrfToken' => $this->csrfToken(),
                'error' => 'Correo o contraseña incorrectos, o cuenta no activa.',
                'message' => null,
                'old' => ['correo' => $email],
            ]);
            return;
        }

        session_regenerate_id(true);
        $sessionUser = [
            'id' => (int) $user['id_usuario'],
            'name' => trim($user['nombres'] . ' ' . $user['apellidos']),
            'email' => $user['correo'],
            'role' => $user['role'],
        ];

        if ($user['role'] === 'administrator') {
            $sessionUser['administrator_id'] = $user['role_id'];
            $destination = '/';
        } elseif ($user['role'] === 'student') {
            $sessionUser['student_id'] = $user['role_id'];
            $destination = '/mi-cuenta';
        } else {
            $sessionUser['instructor_id'] = $user['role_id'];
            $destination = '/instructor';
        }

        $_SESSION['user'] = $sessionUser;
        $this->redirect($destination);
    }

    /**
     * Cierra la sesion activa y redirige al acceso comun.
     */
    public function logout(): void
    {
        $this->verifyCsrfToken();
        $role = $_SESSION['user']['role'] ?? null;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $parameters['path'],
                'domain' => $parameters['domain'],
                'secure' => $parameters['secure'],
                'httponly' => $parameters['httponly'],
                'samesite' => $parameters['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        $this->redirect('/login');
    }

    private function validateAdministrator(
        string $email,
        string $firstName,
        string $lastName,
        string $password,
        string $passwordConfirmation
    ): ?string {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un correo electrónico válido.';
        }

        if ($firstName === '' || $lastName === '') {
            return 'Nombres y apellidos son obligatorios.';
        }

        if (strlen($password) < 12) {
            return 'La contraseña debe tener al menos 12 caracteres.';
        }

        if ($password !== $passwordConfirmation) {
            return 'Las contraseñas no coinciden.';
        }

        return null;
    }

    private function renderSetupError(string $error, array $old): void
    {
        http_response_code(422);
        $this->render('auth/setup', [
            'title' => 'Configuración inicial del administrador',
            'csrfToken' => $this->csrfToken(),
            'error' => $error,
            'old' => $old,
        ]);
    }
}
