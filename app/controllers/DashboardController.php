<?php

declare(strict_types=1);

/**
 * Prepara la pagina inicial segun el usuario autenticado.
 */
final class DashboardController extends BaseController
{
    /**
     * Valida el acceso, obtiene los datos necesarios y muestra la vista del modulo.
     */
    public function index(): void
    {
        $csrfToken = $this->csrfToken();
        $this->render('dashboard', [
            'user' => $_SESSION['user'] ?? null,
            'csrfToken' => $csrfToken,
        ]);
    }
}
