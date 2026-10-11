<?php

declare(strict_types=1);

/** Formulario de configuracion de la primera cuenta administradora. */

?>
<main class="container auth-container">
        <section class="card">
            <h1>Crear administrador inicial</h1>
            <p>Este formulario solo estará disponible mientras no exista un administrador.</p>
            <?php if ($error !== null): ?>
                <p class="notice notice-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <form action="/setup" method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo<input type="email" name="correo" maxlength="254" required value="<?= e($old['correo'] ?? '') ?>"></label>
                <label>Nombres<input name="nombres" maxlength="30" required value="<?= e($old['nombres'] ?? '') ?>"></label>
                <label>Apellidos<input name="apellidos" maxlength="30"required value="<?= e($old['apellidos'] ?? '') ?>"></label>
                <label>Contraseña de tener mínimo 12 caracteres<input type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="Ejemplo: As!*{d/*QKS-W|14DF}$DF"></label>
                <label>Confirmar contraseña<input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></label>
                <button class="button" type="submit">Crear administrador</button>
            </form>
        </section>
    </main>
