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
                <label>Correo electrónico<input type="email" name="Correo" maxlength="254" required autocomplete="email" placeholder="Ej. Nombre@dominio.com" value="<?= e($old['Correo'] ?? '') ?>"></label>
                <label>Nombres<input name="Nombres" maxlength="100" required autocomplete="given-name" placeholder="Ej. Ana María" value="<?= e($old['Nombres'] ?? '') ?>"></label>
                <label>Apellidos<input name="Apellidos" maxlength="100" required autocomplete="family-name" placeholder="Ej. López García" value="<?= e($old['Apellidos'] ?? '') ?>"></label>
                <label>Contraseña (mínimo 12 caracteres)<input type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="Escribe una contraseña de 12 caracteres o más"></label>
                <label>Confirmar contraseña<input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></label>
                <button class="button" type="submit">Crear administrador</button>
            </form>
        </section>
    </main>
