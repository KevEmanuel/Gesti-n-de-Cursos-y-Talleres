<?php

declare(strict_types=1);

abstract class BaseController
{
    /**
     * Restringe la acción al rol administrador y envía al login si no está autorizado.
     */
    protected function requireAdministrator(): void
    {
        $this->startSession();
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || ($user['role'] ?? null) !== 'administrator') {
            $this->redirect('/login');
        }
    }

    /**
     * Restringe la acción al rol estudiante y devuelve los datos de la sesión autenticada.
     *
     * @return array<string, mixed> Datos del estudiante autenticado.
     */
    protected function requireStudent(): array
    {
        $this->startSession();
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || ($user['role'] ?? null) !== 'student') {
            $this->redirect('/login');
        }

        return $user;
    }

    /**
     * Restringe la acción al rol instructor, valida su identificador y devuelve su sesión.
     *
     * @return array<string, mixed> Datos del instructor autenticado.
     */
    protected function requireInstructor(): array
    {
        $this->startSession();
        $user = $_SESSION['user'] ?? null;
        $instructorId = is_array($user)
            ? filter_var($user['instructor_id'] ?? null, FILTER_VALIDATE_INT)
            : false;

        if (
            !is_array($user)
            || ($user['role'] ?? null) !== 'instructor'
            || $instructorId === false
            || $instructorId === null
            || $instructorId < 1
        ) {
            $this->redirect('/login');
        }

        return $user;
    }

    /**
     * Obtiene un token CSRF aleatorio para proteger formularios de la sesión.
     */
    protected function csrfToken(): string
    {
        $this->startSession();
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        return $_SESSION['csrf_token'];
    }

    /**
     * Rechaza con HTTP 403 cualquier solicitud cuyo token CSRF no coincida con la sesión.
     */
    protected function verifyCsrfToken(): void
    {
        $this->startSession();
        $submittedToken = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (!is_string($submittedToken) || !is_string($sessionToken) || !hash_equals($sessionToken, $submittedToken)) {
            http_response_code(403);
            echo 'Solicitud no válida. Actualiza la página e inténtalo nuevamente.';
            exit;
        }
    }

    /**
     * Incluye la vista seleccionada y la presenta dentro del diseño HTML compartido.
     *
     * @param string $view Ruta lógica de la vista bajo app/views, sin extensión.
     * @param array<string, mixed> $data Variables disponibles para la vista.
     *
     * @throws RuntimeException Si no existe el archivo de vista indicado.
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewPath = __DIR__ . '/../views/' . $view;
        $indexView = $viewPath . '/index.php';
        $singleFileView = $viewPath . '.php';

        if (is_file($indexView)) {
            $viewFile = $indexView;
        } elseif (is_file($singleFileView)) {
            $viewFile = $singleFileView;
        } else {
            throw new RuntimeException('No se encontró la vista solicitada.');
        }

        ob_start();
        try {
            require $viewFile;
            $content = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        require __DIR__ . '/../views/layouts/main.php';
    }

    /** Envía una redirección HTTP 303 y finaliza la solicitud actual. */
    protected function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }

    /** Inicia la sesión con opciones de cookie seguras si aún no está activa. */
    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'httponly' => true,
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }
}
