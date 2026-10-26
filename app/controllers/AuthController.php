<?php

declare(strict_types=1);

/**
 * Gestiona el acceso comun, la autenticacion por rol y el cierre de sesion.
 */
final class AuthController extends BaseController
{
    private Usuario $users;

    /** Crea el modelo de usuarios con la conexión PDO de la aplicación. */
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
            'contrasena_actualizada' => 'Tu contraseña se actualizó. Ya puedes iniciar sesión.',
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
     * Muestra el formulario para solicitar un enlace de recuperación.
     */
    public function forgotPassword(): void
    {
        $this->render('auth/forgot-password', [
            'title' => 'Recuperar contraseña | Curso Taller',
            'csrfToken' => $this->csrfToken(),
            'error' => null,
            'message' => (string) ($_GET['estado'] ?? '') === 'solicitud'
                ? 'Si la cuenta existe y puede recibir otra solicitud, enviaremos instrucciones al correo registrado.'
                : null,
            'old' => [],
        ]);
    }

    /**
     * Procesa solicitudes de recuperación sin revelar si existe el correo.
     */
    public function requestPasswordReset(): void
    {
        $this->verifyCsrfToken();

        $email = trim((string) ($_POST['correo'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 254) {
            http_response_code(422);
            $this->renderForgotPasswordError('Ingresa un correo electrónico válido.', $email);
            return;
        }

        $tokenHash = null;

        try {
            $mailer = new PasswordResetMailer();
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $user = $this->users->createPasswordResetToken($email, $tokenHash);

            if ($user !== null) {
                $mailer->send(
                    $user['correo'],
                    trim($user['nombres'] . ' ' . $user['apellidos']),
                    $token
                );
            }
        } catch (PHPMailer\PHPMailer\Exception | RuntimeException $exception) {
            if ($tokenHash !== null) {
                $this->users->revokePasswordResetToken($tokenHash);
            }
            error_log('Error al enviar correo de recuperación de contraseña: ' . $exception->getMessage());
            http_response_code(503);
            $this->renderForgotPasswordError(
                'No pudimos procesar la solicitud. Contacta al administrador para que te asigne una contraseña nueva.',
                $email
            );
            return;
        }

        $this->redirect('/password/recuperar?estado=solicitud');
    }

    /**
     * Muestra el formulario con el formato esperado, configuracion usando token
     */
    public function resetPasswordForm(): void
    {
        $this->setPasswordResetHeaders();
        $token = (string) ($_GET['token'] ?? '');
        $validToken = preg_match('/\A[a-f0-9]{64}\z/', $token) === 1
            && $this->users->hasValidPasswordResetToken(hash('sha256', $token));

        $this->renderResetPasswordForm(
            $validToken ? $token : '',
            $validToken ? null : 'Este enlace no es válido o ya venció. Solicita uno nuevo.'
        );
    }

    /**
     * Valida el formulario 
     */
    public function resetPassword(): void
    {
        $this->setPasswordResetHeaders();
        $this->verifyCsrfToken();

        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1
            || !$this->users->hasValidPasswordResetToken(hash('sha256', $token))) {
            http_response_code(422);
            $this->renderResetPasswordForm('', 'Este enlace no es válido o ya venció. Solicita uno nuevo.');
            return;
        }

        if (mb_strlen($password, 'UTF-8') < 12) {
            http_response_code(422);
            $this->renderResetPasswordForm($token, 'La contraseña debe tener al menos 12 caracteres.');
            return;
        }

        if ($password !== $passwordConfirmation) {
            http_response_code(422);
            $this->renderResetPasswordForm($token, 'Las contraseñas no coinciden.');
            return;
        }

        if (!$this->users->resetPassword(hash('sha256', $token), password_hash($password, PASSWORD_DEFAULT))) {
            http_response_code(422);
            $this->renderResetPasswordForm('', 'Este enlace ya fue utilizado o venció. Solicita uno nuevo.');
            return;
        }

        $this->redirect('/login?mensaje=contrasena_actualizada');
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

    /**
     * Valida correo, datos personales y contraseña del administrador inicial.
     *
     * @return string|null Mensaje de validación o null cuando los datos son válidos.
     */
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

        if (mb_strlen($email, 'UTF-8') > 254) {
            return 'El correo no puede exceder 254 caracteres.';
        }

        if ($firstName === '' || $lastName === '') {
            return 'Nombres y apellidos son obligatorios.';
        }

        if (mb_strlen($firstName, 'UTF-8') > 100 || mb_strlen($lastName, 'UTF-8') > 100) {
            return 'Los nombres y apellidos no pueden exceder 100 caracteres cada uno.';
        }

        if (mb_strlen($password, 'UTF-8') < 12) {
            return 'La contraseña debe tener al menos 12 caracteres.';
        }

        if ($password !== $passwordConfirmation) {
            return 'Las contraseñas no coinciden.';
        }

        return null;
    }

    /** Vuelve a mostrar el formulario inicial con el error y los datos no sensibles. */
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

    /** Vuelve a mostrar el formulario de recuperación conservando el correo ingresado. */
    private function renderForgotPasswordError(string $error, string $email): void
    {
        $this->render('auth/forgot-password', [
            'title' => 'Recuperar contraseña | Curso Taller',
            'csrfToken' => $this->csrfToken(),
            'error' => $error,
            'message' => null,
            'old' => ['correo' => $email],
        ]);
    }

    /** Renderiza la pantalla para cambiar contraseña, ocultando el token si no es válido. */
    private function renderResetPasswordForm(string $token, ?string $error): void
    {
        $this->render('auth/reset-password', [
            'title' => 'Crear nueva contraseña | Curso Taller',
            'csrfToken' => $this->csrfToken(),
            'error' => $error,
            'token' => $token,
        ]);
    }

    /** Evita almacenar en caché la pantalla que contiene un token de recuperación. */
    private function setPasswordResetHeaders(): void
    {
        header('Cache-Control: no-store, max-age=0');
        header('Referrer-Policy: no-referrer');
    }
}
