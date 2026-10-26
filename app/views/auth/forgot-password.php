<?php

declare(strict_types=1);

?>
<main class="auth-experience login-page password-recovery-page">
    <aside class="auth-brand-panel">
        <a class="auth-brand" href="/" aria-label="Formación Continua, inicio">
            <span class="auth-brand-mark" aria-hidden="true">FC</span>
            <span>Formación Continua</span>
        </a>
        <div class="auth-brand-copy">
            <p class="auth-eyebrow">RECUPERA EL ACCESO</p>
            <h2>Volvamos a tu aprendizaje</h2>
            <ul>
                <li>Recibe un enlace seguro en tu correo registrado.</li>
                <li>El enlace vence en una hora y solo funciona una vez.</li>
            </ul>
        </div>
        <p class="auth-brand-footnote">Formación para avanzar hacia tus metas.</p>
    </aside>
    <div class="auth-content">
        <section class="card auth-card">
            <h1>Recuperar contraseña</h1>
            <p>Escribe el correo asociado a tu cuenta. Si existe, enviaremos instrucciones para crear una nueva contraseña.</p>
            <?php if ($message !== null): ?>
                <p class="notice notice-success" role="status"><?= e($message) ?></p>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <p class="notice notice-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <form action="/password/recuperar" method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo electrónico<input type="email" name="correo" maxlength="254" placeholder="usuario@gmail.com" required autocomplete="email" value="<?= e($old['correo'] ?? '') ?>"></label>
                <button class="button" type="submit">Enviar instrucciones</button>
            </form>
            <p class="auth-recovery-help">Si no puedes recibir el correo, solicita al administrador que cambie tu contraseña desde tu cuenta.</p>
            <p class="auth-alternate"><a href="/login">Volver a iniciar sesión</a></p>
        </section>
    </div>
</main>
