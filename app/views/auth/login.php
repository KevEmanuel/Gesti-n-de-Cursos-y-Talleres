<?php

declare(strict_types=1);

/** Formulario unico de acceso para todos los roles. */

?>
<!-- <main class="container auth-container"> cambio efectuado -->
<main class="container auth-container login-page">
         <a href="/">Inicio</a>
        <section class="card">
            <h1>Iniciar sesión</h1>
            <p>Ingresa con tu cuenta.</p>
            <?php if ($message !== null): ?>
                <p class="notice notice-success" role="status"><?= e($message) ?></p>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <p class="notice notice-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <form action="/login" method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo electrónico<input type="email" name="correo" maxlength="254" placeholder="usuario@gmail.com" required autocomplete="username" value="<?= e($old['correo'] ?? '') ?>"></label>
                <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
                <button class="button" type="submit">Entrar</button>
            </form>
        </section>
    </main>
