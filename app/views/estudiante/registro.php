<?php

declare(strict_types=1);

/** Formulario para solicitar una cuenta de estudiante. */

?>
<main class="auth-experience register-page">
    <aside class="auth-brand-panel">
        <a class="auth-brand" href="/catalogo" aria-label="Formación Continua, volver al catálogo">
            <span class="auth-brand-mark" aria-hidden="true">FC</span>
            <span>Formación Continua</span>
        </a>
        <div class="auth-brand-copy">
            <p class="auth-eyebrow">TU PRÓXIMO PASO COMIENZA AQUÍ</p>
            <h2>Únete a una comunidad que sigue aprendiendo</h2>
            <ul>
                <li>Encuentra cursos para desarrollar nuevas habilidades.</li>
                <li>Envía tu solicitud de cuenta en unos minutos.</li>
                <li>Inicia sesión cuando tu cuenta sea aprobada.</li>
            </ul>
        </div>
        <a class="auth-brand-footnote auth-back-link" href="/catalogo">Volver al catálogo</a>
    </aside>
    <div class="auth-content auth-content--wide">
        <section class="card auth-card">
            <h1>Solicitar cuenta</h1>
            <p>Completa tus datos para enviar la solicitud de registro.</p>
            <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
            <form action="/estudiante/registro" method="post" class="student-register-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <!-- Validación de Nombres del estudiante -->
                <label>Nombres<input name="Nombres" maxlength="100" required autocomplete="given-name" class="soloLetras" placeholder="Ej. Ana María" value="<?= e($old['Nombres'] ?? '') ?>"><small>Usa letras, espacios, guiones o apóstrofes.</small></label>
                <!-- Validación de Apellidos del estudiante -->
                <label>Apellidos<input name="Apellidos" maxlength="100" required autocomplete="family-name" class="soloLetras" placeholder="Ej. López García" value="<?= e($old['Apellidos'] ?? '') ?>"><small>Usa letras, espacios, guiones o apóstrofes.</small></label>
                <label>Correo electrónico<input type="email" name="Correo" maxlength="254" required autocomplete="email" placeholder="Ej. Nombre@dominio.com" value="<?= e($old['Correo'] ?? '') ?>"></label>
                <!-- Validación del celular en el registro público -->
                <label>Teléfono celular<input type="tel" name="Telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" autocomplete="tel" title="Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." placeholder="Ej. 71234567" value="<?= e($old['Telefono'] ?? '') ?>"><small>Opcional. 8 dígitos, comenzando con 5, 6 o 7.</small></label>
                <label>Edad (17 a 70 años)<input type="number" name="Edad" min="17" max="70" step="1" required placeholder="Ej. 25" value="<?= e((string) ($old['Edad'] ?? '')) ?>"></label>
                <label>Género
                    <select name="Genero">
                        <option value="">Sin especificar</option>
                        <?php foreach (['Masculino', 'Femenino', 'Otro'] as $gender): ?>
                            <option value="<?= $gender ?>" <?= ($old['Genero'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Contraseña (mínimo 12 caracteres)<input type="password" name="password" minlength="12" autocomplete="new-password" required placeholder="Escribe 12 caracteres o más"></label>
                <label>Confirmar contraseña<input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required></label>
                <button class="button" type="submit">Enviar solicitud</button>
            </form>
            <p class="auth-alternate">¿Ya tienes cuenta aprobada? <a href="/login">Inicia sesión</a>.</p>
        </section>
    </div>
</main>
