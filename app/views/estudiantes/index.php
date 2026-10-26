<?php

declare(strict_types=1);

/** Formulario y listado administrativo de estudiantes. */

?>
<main class="container">
        <header class="page-header">
            <div><a href="/">Volver al dashboard</a><h1><?= e($title) ?></h1></div>
        </header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>

        <section class="card profile-list">
            <div class="toolbar">
                <div>
                    <h2>Solicitudes para crear una cuenta</h2>
                    <p>Revisa los datos iniciales. El carnet se genera al aprobar la cuenta.</p>
                </div>
                <label>Buscar<input type="search" placeholder="Buscar solicitudes de cuenta" data-table-search></label>
            </div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Fecha</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($applications as $application): ?>
                            <tr>
                                <td><?= e((string) $application['creada_en']) ?></td>
                                <td><?= e($application['apellidos'] . ', ' . $application['nombres']) ?></td>
                                <td><?= e((string) $application['correo']) ?></td>
                                <td><?= e((string) ($application['telefono'] ?? '—')) ?></td>
                                <td><?= e((string) $application['estado_solicitud']) ?></td>
                                <td>
                                    <?php if ($application['estado_solicitud'] === 'Pendiente'): ?>
                                        <form action="/estudiantes/solicitud/estado" method="post" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="id_solicitud_estudiante" value="<?= (int) $application['id_solicitud_estudiante'] ?>">
                                            <input type="hidden" name="estado_solicitud" value="Aprobada">
                                            <button type="submit" class="button">Aprobar</button>
                                        </form>
                                        <form action="/estudiantes/solicitud/estado" method="post" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="id_solicitud_estudiante" value="<?= (int) $application['id_solicitud_estudiante'] ?>">
                                            <input type="hidden" name="estado_solicitud" value="Rechazada">
                                            <button type="submit" class="button button-secondary">Rechazar</button>
                                        </form>
                                    <?php else: ?>
                                        <span>Revisada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $applications === [] ? '' : 'hidden' ?>><td colspan="6" class="empty-state">No hay solicitudes de cuenta.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Registrar estudiante</h2>
            <p>El carnet se genera automáticamente con las iniciales de ambos apellidos, o las dos primeras letras si solo hay uno, y cuatro dígitos.</p>
            <form action="/estudiantes/crear" method="post" class="profile-create-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo electrónico<input type="email" name="correo" maxlength="254" required autocomplete="email" placeholder="Ej. nombre@dominio.com" value="<?= e($old['correo'] ?? '') ?>"></label>
                <label>Nombres<input name="nombres" maxlength="100" required autocomplete="given-name" placeholder="Ej. Ana María" value="<?= e($old['nombres'] ?? '') ?>"><small>Usa letras, espacios, guiones o apóstrofes.</small></label>
                <label>Apellidos<input name="apellidos" maxlength="100" required autocomplete="family-name" placeholder="Ej. López García" value="<?= e($old['apellidos'] ?? '') ?>"><small>Usa letras, espacios, guiones o apóstrofes.</small></label>
                <!-- Validación del celular al registrar un estudiante -->
                <label>Teléfono celular<input type="tel" name="telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." placeholder="Ej. 71234567" value="<?= e($old['telefono'] ?? '') ?>"><small>Opcional. 8 dígitos, comenzando con 5, 6 o 7.</small></label>
                <label>Edad (17 a 70 años)<input type="number" name="edad" min="17" max="70" step="1" required placeholder="Ej. 25" value="<?= e((string) ($old['edad'] ?? '')) ?>"></label>
                <label>Género
                    <select name="genero">
                        <option value="">Sin especificar</option>
                        <?php foreach (['Masculino', 'Femenino', 'Otro'] as $gender): ?>
                            <option value="<?= $gender ?>" <?= ($old['genero'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Contraseña inicial (mínimo 12 caracteres)<input type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="Escribe 12 caracteres o más"></label>
                <button type="submit" class="button">Crear estudiante</button>
            </form>
        </section>

        <section class="card profile-list">
            <div class="toolbar">
                <h2>Estudiantes registrados</h2>
                <label>Buscar<input type="search" placeholder="Buscar estudiantes" data-table-search></label>
            </div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Carnet</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Edad</th><th>Género</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><span class="profile-carnet"><?= e((string) $student['carnet']) ?></span></td>
                                <td colspan="6">
                                    <form action="/estudiantes/actualizar" method="post" class="profile-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_estudiante" value="<?= (int) $student['id_estudiante'] ?>">
                                        <label><span class="visually-hidden">Nombre</span><input name="nombres" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $student['nombres']) ?>"></label>
                                        <label><span class="visually-hidden">Apellidos</span><input name="apellidos" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $student['apellidos']) ?>"></label>
                                        <label><span class="visually-hidden">Correo</span><input type="email" name="correo" maxlength="254" required value="<?= e((string) $student['correo']) ?>"></label>
                                        <!-- Validación del celular al modificar un estudiante -->
                                        <label><span class="visually-hidden">Teléfono</span><input type="tel" name="telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Opcional. Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." value="<?= e((string) ($student['telefono'] ?? '')) ?>"></label>
                                        <label><span class="visually-hidden">Edad (17 a 70 años)</span><input type="number" name="edad" min="17" max="70" step="1" required title="La edad debe estar entre 17 y 70 años." value="<?= e((string) ($student['edad'] ?? '')) ?>"></label>
                                        <label><span class="visually-hidden">Género</span>
                                            <select name="genero"><option value="">Sin especificar</option>
                                                <?php foreach (['Masculino', 'Femenino', 'Otro'] as $gender): ?>
                                                    <option value="<?= $gender ?>" <?= $student['genero'] === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <span class="account-status <?= $student['estado_cuenta'] === 'Activo' ? 'account-status--active' : 'account-status--inactive' ?>"><?= e((string) $student['estado_cuenta']) ?></span>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td class="student-actions-cell">
                                    <div class="student-row-actions">
                                    <form action="/estudiantes/estado" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_estudiante" value="<?= (int) $student['id_estudiante'] ?>">
                                        <?php $nextStatus = $student['estado_cuenta'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>
                                        <input type="hidden" name="estado_cuenta" value="<?= $nextStatus ?>">
                                        <button type="submit" class="button button-secondary"><?= $nextStatus === 'Activo' ? 'Activar' : 'Desactivar' ?></button>
                                    </form>
                                    <form action="/estudiantes/eliminar" method="post" onsubmit="return confirm('¿Eliminar este estudiante? Solo se permite si no tiene historial de cursos.')">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_estudiante" value="<?= (int) $student['id_estudiante'] ?>">
                                        <button type="submit" class="button button-secondary">Eliminar</button>
                                    </form>
                                    <details class="password-reset-details">
                                        <summary class="button button-secondary">Cambiar contraseña</summary>
                                        <form action="/estudiantes/contrasena" method="post" class="password-reset-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="id_estudiante" value="<?= (int) $student['id_estudiante'] ?>">
                                            <label>Nueva contraseña
                                                <input type="password" name="password" minlength="12" required autocomplete="new-password">
                                            </label>
                                            <label>Confirmar contraseña
                                                <input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password">
                                            </label>
                                            <small>Mínimo 12 caracteres. Comparte la contraseña nueva de forma segura.</small>
                                            <button type="submit" class="button">Guardar contraseña</button>
                                        </form>
                                    </details>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $students === [] ? '' : 'hidden' ?>><td colspan="8" class="empty-state">No hay estudiantes registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
