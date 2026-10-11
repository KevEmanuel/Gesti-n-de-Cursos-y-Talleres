<?php

declare(strict_types=1);

/**
 * Centraliza autorizacion, proteccion CSRF, renderizado de vistas y redirecciones.
 */
abstract class BaseController
{
    /** Verifica que la sesión pertenezca a un administrador. */
    protected function requireAdministrator(): void
    {
        $this->startSession();
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || ($user['role'] ?? null) !== 'administrator') {
            $this->redirect('/login');
        }
    }

    /** Verifica la sesión estudiantil y devuelve sus datos. */
    protected function requireStudent(): array
    {
        $this->startSession();
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || ($user['role'] ?? null) !== 'student') {
            $this->redirect('/login');
        }

        return $user;
    }

    /** Verifica la sesión del instructor y devuelve sus datos. */
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

    /** Obtiene o genera el token CSRF de la sesión actual. */
    protected function csrfToken(): string
    {
        $this->startSession();
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        return $_SESSION['csrf_token'];
    }

    /** Rechaza solicitudes cuyo token CSRF no coincide con la sesión. */
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

    /** Renderiza la vista dentro del diseño HTML compartido. */
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

    /** Renderiza una vista de recurso con sus columnas configuradas. */
    protected function renderModule(string $view, string $title, array $columns): void
    {
        $this->render($view, [
            'title' => $title,
            'columns' => $columns,
        ]);
    }

    /** Redirige con el código HTTP usado para solicitudes POST. */
    protected function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }

    /** Inicia una sesión con opciones seguras cuando aún no existe. */
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
