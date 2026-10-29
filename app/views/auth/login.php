<?php

declare(strict_types=1);

/* Formulario unico de acceso para todos los roles. */

?>
<main class="auth-experience login-page">
    <aside class="auth-brand-panel">
        <a class="auth-brand" href="/" aria-label="Formación Continua, inicio">
            <span class="auth-brand-mark" aria-hidden="true">FC</span>
            <span>Formación Continua</span>
        </a>
        <div class="auth-brand-copy">
            <p class="auth-eyebrow">CRECE CON CADA APRENDIZAJE</p>
            <h2>Aprende algo nuevo este ciclo</h2>
            <ul>
                <li>Explora cursos y talleres en distintas áreas.</li>
                <li>Elige la Modalidad que mejor se adapte a ti.</li>
                <li>Consulta tus solicitudes desde tu cuenta.</li>
            </ul>
        </div>
        <p class="auth-brand-footnote">Formación para avanzar hacia tus metas.</p>
    </aside>
    <div class="auth-content">
        <section class="card auth-card">
            <h1>Iniciar sesión</h1>
            <p>Accede con tu cuenta.</p>
            <?php if ($message !== null): ?>
                <p class="notice notice-success" role="status"><?= e($message) ?></p>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <p class="notice notice-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <form action="/login" method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo electrónico<input type="email" name="Correo" maxlength="254" placeholder="usuario@gmail.com" required autocomplete="username" value="<?= e($old['Correo'] ?? '') ?>"></label>
                <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
                <a class="auth-forgot-link" href="/password/recuperar">¿Olvidaste tu contraseña?</a>
                <button class="button" type="submit">Entrar</button>
            </form>
            <p class="auth-alternate">¿Aún no tienes cuenta? <a href="/estudiante/registro">Solicita una cuenta de estudiante</a>.</p>
        </section>
    </div>
</main>
