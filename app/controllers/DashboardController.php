<?php

declare(strict_types=1);

/**
 * Prepara la pagina inicial segun el usuario autenticado.
 */
final class DashboardController extends BaseController
{
    /** Muestra la página inicial con la sesión y el token CSRF disponibles para la vista. */
    public function index(): void
    {
        $csrfToken = $this->csrfToken();
        $this->render('dashboard', [
            'user' => $_SESSION['user'] ?? null,
            'csrfToken' => $csrfToken,
        ]);
    }
}
