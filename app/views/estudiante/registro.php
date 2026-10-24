<?php

declare(strict_types=1);

/** Formulario para solicitar una cuenta de estudiante. */

?>
<main class="container auth-container">
        <a href="/catalogo"> Volver al catálogo</a>
        <section class="card">
            <h1 class="eyebrow" >Solicitud de Cuenta</h1>
            <section>
                 <p>Proporcione los siguientes datos</p>
            </section>
            <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
            <form action="/estudiante/registro" method="post" class="student-register-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <!-- Validación de nombres del estudiante -->
                <label>Nombres<input name="nombres" maxlength="20" required autocomplete="given-name" class="soloLetras" value="<?= e($old['nombres'] ?? '') ?>"></label>
                <!-- Validación de apellidos del estudiante -->
                <label>Apellidos<input name="apellidos" maxlength="20" required autocomplete="family-name" class="soloLetras" value="<?= e($old['apellidos'] ?? '') ?>"></label>
                <label>Correo<input type="email" name="correo" maxlength="30" required autocomplete="email" placeholder="ejemplo@universidad.edu" value="<?= e($old['correo'] ?? '') ?>"></label>
                <!-- Validación del celular en el registro público -->
                <label>Teléfono<input type="tel" name="telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" autocomplete="tel"title="El celular debe tener 8 dígitos y comenzar con 5, 6 o 7" value="<?= e($old['telefono'] ?? '') ?>"></label>
                <label>Edad<input type="number" name="edad" min="0" max="80" value="<?= e((string) ($old['edad'] ?? '')) ?>"></label>
                <label>Género
                    <select name="genero">
                        <option value="">Sin especificar</option>
                        <?php foreach (['Masculino', 'Femenino', 'Otro'] as $gender): ?>
                            <option value="<?= $gender ?>" <?= ($old['genero'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Contraseña para tu cuenta (mínimo 12 caracteres)<input type="password" name="password" minlength="12" autocomplete="new-password" required></label>
                <label>Confirmar contraseña<input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required></label>
                <button class="button" type="submit">Enviar solicitud</button>
            </form>
            <p>¿Ya tienes cuenta aprobada? <a href="/login">Inicia sesión</a>.</p>
        </section>

    </main>
