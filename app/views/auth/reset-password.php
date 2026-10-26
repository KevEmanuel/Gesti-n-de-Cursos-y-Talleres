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
            <p class="auth-eyebrow">NUEVA CONTRASEÑA</p>
            <h2>Protege tu cuenta</h2>
            <ul>
                <li>Usa una contraseña de al menos 12 caracteres.</li>
                <li>El enlace de recuperación es personal y de un solo uso.</li>
            </ul>
        </div>
        <p class="auth-brand-footnote">Formación para avanzar hacia tus metas.</p>
    </aside>
    <div class="auth-content">
        <section class="card auth-card">
            <h1>Crear nueva contraseña</h1>
            <p>Elige una contraseña segura para volver a tu cuenta.</p>
            <?php if ($error !== null): ?>
                <p class="notice notice-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <?php if ($token !== ''): ?>
                <form action="/password/restablecer" method="post" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <label>Nueva contraseña<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
                    <label>Confirma la contraseña<input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></label>
                    <button class="button" type="submit">Guardar contraseña</button>
                </form>
            <?php endif; ?>
            <p class="auth-alternate"><a href="/login">Volver a iniciar sesión</a></p>
        </section>
    </div>
</main>
