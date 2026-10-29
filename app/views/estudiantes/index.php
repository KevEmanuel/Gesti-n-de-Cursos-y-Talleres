<?php

declare(strict_types=1);

/**Formura 
 * administracion
 *estudiantes.
 */

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
                    <p> <strong>Nota: </strong> El Carnet se genera al aprobar la cuenta.</p>
                </div>
                <label>Buscar<input type="search" placeholder="Buscar solicitudes de cuenta" data-table-search></label>
            </div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Fecha</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($applications as $application): ?>
                            <tr>
                                <td><?= e((string) $application['CreadaEn']) ?></td>
                                <td><?= e($application['Apellidos'] . ', ' . $application['Nombres']) ?></td>
                                <td><?= e((string) $application['Correo']) ?></td>
                                <td><?= e((string) ($application['Telefono'] ?? '—')) ?></td>
                                <td><?= e((string) $application['EstadoSolicitud']) ?></td>
                                <td>
                                    <?php if ($application['EstadoSolicitud'] === 'Pendiente'): ?>
                                        <form action="/estudiantes/solicitud/estado" method="post" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="IdSolicitudEstudiante" value="<?= (int) $application['IdSolicitudEstudiante'] ?>">
                                            <input type="hidden" name="EstadoSolicitud" value="Aprobada">
                                            <button type="submit" class="button">Aprobar</button>
                                        </form>
                                        <form action="/estudiantes/solicitud/estado" method="post" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="IdSolicitudEstudiante" value="<?= (int) $application['IdSolicitudEstudiante'] ?>">
                                            <input type="hidden" name="EstadoSolicitud" value="Rechazada">
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
            <h2>Registrar un nuevo Estudiante</h2>
            <p><strong>Nota: </strong> El Carnet se genera automáticamente con las iniciales de los apellidos y cuatro dígitos.</p>
            <form action="/estudiantes/crear" method="post" class="profile-create-form student-create-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo electrónico<input type="email" name="Correo" maxlength="254" required autocomplete="email" placeholder="Ej. Nombre@dominio.com" value="<?= e($old['Correo'] ?? '') ?>"></label>
                <label>Nombres<input name="Nombres" maxlength="100" required autocomplete="given-name" placeholder="Ej. Ana María" value="<?= e($old['Nombres'] ?? '') ?>"></label>
                <label>Apellidos<input name="Apellidos" maxlength="100" required autocomplete="family-name" placeholder="Ej. López García" value="<?= e($old['Apellidos'] ?? '') ?>"></label>
                <label>Teléfono celular<input type="tel" name="Telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." placeholder="Ej. 71234567" value="<?= e($old['Telefono'] ?? '') ?>"></label>
                <label>Edad<input type="number" name="Edad" min="17" max="70" step="1" required placeholder="Ej. 25" value="<?= e((string) ($old['Edad'] ?? '')) ?>"></label>
                <label>Género
                    <select name="Genero">
                        <option value="">Sin especificar</option>
                        <?php foreach (['Masculino', 'Femenino', 'Otro'] as $gender): ?>
                            <option value="<?= $gender ?>" <?= ($old['Genero'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Contraseña<input type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="Escribe 12 caracteres o más"></label>
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
                    <thead><tr><th>Fichas de estudiantes</th></tr></thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr class="student-record-row">
                                <td>
                                    <article class="student-card">
                                        <header class="student-card-header">
                                            <span class="profile-carnet"><?= e((string) $student['Carnet']) ?></span>
                                            <span class="student-card-title"><?= e($student['Nombres'] . ' ' . $student['Apellidos']) ?></span>
                                            <span class="account-status <?= $student['Estado_Cuenta'] === 'Activo' ? 'account-status--active' : 'account-status--inactive' ?>"><?= e((string) $student['Estado_Cuenta']) ?></span>
                                            <aside class="student-row-actions">
                                                <details class="student-actions-menu">
                                                    <summary aria-label="Acciones para <?= e($student['Nombres'] . ' ' . $student['Apellidos']) ?>" aria-haspopup="true">
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                                                        </svg>
                                                        <span>Acciones</span>
                                                    </summary>
                                                    <div class="student-actions-menu-panel">
                                                        <form action="/estudiantes/estado" method="post">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                            <input type="hidden" name="IdEstudiante" value="<?= (int) $student['IdEstudiante'] ?>">
                                                            <?php $nextStatus = $student['Estado_Cuenta'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>
                                                            <input type="hidden" name="Estado_Cuenta" value="<?= $nextStatus ?>">
                                                            <button type="submit" class="student-action-option"><?= $nextStatus === 'Activo' ? 'Activar' : 'Desactivar' ?></button>
                                                        </form>
                                                        <details class="password-reset-details">
                                                            <summary class="student-action-option">Cambiar contraseña</summary>
                                                            <form action="/estudiantes/contrasena" method="post" class="password-reset-form">
                                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                                <input type="hidden" name="IdEstudiante" value="<?= (int) $student['IdEstudiante'] ?>">
                                                                <label>Nueva contraseña
                                                                    <input type="password" name="password" minlength="12" required autocomplete="new-password">
                                                                </label>
                                                                <label>Confirmar contraseña
                                                                    <input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password">
                                                                </label>
                                                                <small>Mínimo 12 caracteres confirmar contraseña nueva</small>
                                                                <button type="submit" class="button">Guardar contraseña</button>
                                                            </form>
                                                        </details>
                                                        <form action="/estudiantes/eliminar" method="post" onsubmit="return confirm('¿Eliminar este estudiante? Solo se permite si no tiene historial de cursos.')">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                            <input type="hidden" name="IdEstudiante" value="<?= (int) $student['IdEstudiante'] ?>">
                                                            <button type="submit" class="student-action-option student-action-option--danger">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </details>
                                            </aside>
                                        </header>
                                        <div class="student-card-content">
                                            <form action="/estudiantes/actualizar" method="post" class="student-edit-form">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="IdEstudiante" value="<?= (int) $student['IdEstudiante'] ?>">
                                                <div class="student-edit-fields">
                                                    <label>Nombres<input name="Nombres" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $student['Nombres']) ?>"></label>
                                                    <label>Apellidos<input name="Apellidos" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $student['Apellidos']) ?>"></label>
                                                    <label>Correo electrónico<input type="email" name="Correo" maxlength="254" required value="<?= e((string) $student['Correo']) ?>"></label>
                                                    <label>Teléfono celular<input type="tel" name="Telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Opcional. Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." value="<?= e((string) ($student['Telefono'] ?? '')) ?>"></label>
                                                    <label>Edad<input type="number" name="Edad" min="17" max="70" step="1" required title="La Edad debe estar entre 17 y 70 años." value="<?= e((string) ($student['Edad'] ?? '')) ?>"></label>
                                                    <label>Género
                                            <select name="Genero"><option value="">Sin especificar</option>
                                                <?php foreach (['Masculino', 'Femenino', 'Otro'] as $gender): ?>
                                                    <option value="<?= $gender ?>" <?= $student['Genero'] === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                                    </label>
                                                </div>
                                                <div class="student-edit-footer">
                                                    <button type="submit" class="button">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </article>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $students === [] ? '' : 'hidden' ?>><td class="empty-state">No hay estudiantes registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
