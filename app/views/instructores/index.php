<?php

declare(strict_types=1);

/** Formulario y listado administrativo de instructores. */

?>
<main class="container">
        <header class="page-header">
            <div><a href="/">Volver al dashboard</a><h1><?= e($title) ?></h1></div>
        </header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>

        <section class="card">
            <h2>Registrar instructor</h2>
            <p>El carnet se genera automáticamente con las iniciales de ambos apellidos, o las dos primeras letras si solo hay uno, y cuatro dígitos.</p>
            <p>Para reasignar cursos al desactivar o eliminar una cuenta, debe haber otro instructor activo.</p>
            <form action="/instructores/crear" method="post" class="profile-create-form instructor-create-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo electrónico<input type="email" name="correo" maxlength="254" required autocomplete="email" placeholder="Ej. nombre@dominio.com" value="<?= e($old['correo'] ?? '') ?>"></label>
                <label>Nombres<input name="nombres" maxlength="100" required autocomplete="given-name" placeholder="Ej. Ana María" value="<?= e($old['nombres'] ?? '') ?>"><small>Usa letras, espacios, guiones o apóstrofes.</small></label>
                <label>Apellidos<input name="apellidos" maxlength="100" required autocomplete="family-name" placeholder="Ej. López García" value="<?= e($old['apellidos'] ?? '') ?>"><small>Usa letras, espacios, guiones o apóstrofes.</small></label>
                <!-- Validación del celular al registrar un instructor -->
                <label>Teléfono celular<input type="tel" name="telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." placeholder="Ej. 71234567" value="<?= e($old['telefono'] ?? '') ?>"><small>Opcional. 8 dígitos, comenzando con 5, 6 o 7.</small></label>
                <label class="specialty-field">Especialidad<input class="specialty-input" name="especialidad" maxlength="255" placeholder="Ej. Diseño gráfico" value="<?= e($old['especialidad'] ?? '') ?>"><small>Opcional; escribe al menos una letra.</small></label>
                <label>Contraseña inicial (mínimo 12 caracteres)<input type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="Escribe 12 caracteres o más"></label>
                <button type="submit" class="button">Crear instructor</button>
            </form>
        </section>

        <section class="card profile-list">
            <div class="toolbar">
                <h2>Instructores registrados</h2>
                <label>Buscar<input type="search" placeholder="Buscar instructores" data-table-search></label>
            </div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Carnet</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Especialidad</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($instructors as $instructor): ?>
                            <?php
                            $assignedCourses = (int) $instructor['cursos_asignados'];
                            $replacementInstructors = array_values(array_filter(
                                $instructors,
                                static fn (array $replacement): bool =>
                                    (int) $replacement['id_instructor'] !== (int) $instructor['id_instructor']
                                    && $replacement['estado_cuenta'] === 'Activo'
                            ));
                            ?>
                            <tr>
                                <td><span class="profile-carnet"><?= e((string) $instructor['carnet']) ?></span></td>
                                <td colspan="5">
                                    <form action="/instructores/actualizar" method="post" class="profile-edit-form instructor-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                        <label><span class="visually-hidden">Nombres</span><input name="nombres" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $instructor['nombres']) ?>"></label>
                                        <label><span class="visually-hidden">Apellidos</span><input name="apellidos" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $instructor['apellidos']) ?>"></label>
                                        <label><span class="visually-hidden">Correo</span><input type="email" name="correo" maxlength="254" required value="<?= e((string) $instructor['correo']) ?>"></label>
                                        <!-- Validación del celular al modificar un instructor -->
                                        <label><span class="visually-hidden">Teléfono</span><input type="tel" name="telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Opcional. Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." value="<?= e((string) ($instructor['telefono'] ?? '')) ?>"></label>
                                        <label class="specialty-field"><span class="visually-hidden">Especialidad</span><input class="specialty-input" name="especialidad" maxlength="255" placeholder="Ej. Diseño gráfico" title="Opcional; si la completas, incluye al menos una letra." value="<?= e((string) ($instructor['especialidad'] ?? '')) ?>"></label>
                                        <span class="account-status <?= $instructor['estado_cuenta'] === 'Activo' ? 'account-status--active' : 'account-status--inactive' ?>"><?= e((string) $instructor['estado_cuenta']) ?></span>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td class="instructor-actions-cell">
                                    <div class="instructor-row-actions">
                                    <form action="/instructores/estado" method="post" class="instructor-action-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                        <?php $nextStatus = $instructor['estado_cuenta'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>
                                        <input type="hidden" name="estado_cuenta" value="<?= $nextStatus ?>">
                                        <?php if ($nextStatus === 'Inactivo' && $assignedCourses > 0): ?>
                                            <div class="instructor-action-field">
                                                <?php if ($replacementInstructors !== []): ?>
                                                    <span class="instructor-field-note">Selecciona quién recibirá <?= $assignedCourses ?> curso(s). La cuenta quedará inactiva.</span>
                                                    <label class="visually-hidden" for="replace-status-<?= (int) $instructor['id_instructor'] ?>">Instructor que recibirá los cursos</label>
                                                    <select id="replace-status-<?= (int) $instructor['id_instructor'] ?>" name="id_instructor_reemplazo" required>
                                                        <option value="">Selecciona instructor</option>
                                                        <?php foreach ($replacementInstructors as $replacement): ?>
                                                            <option value="<?= (int) $replacement['id_instructor'] ?>"><?= e($replacement['apellidos'] . ', ' . $replacement['nombres']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <span class="instructor-field-note instructor-field-note--persistent">No hay otro instructor activo. Registra o activa uno para reasignar los cursos.</span>
                                                    <input type="hidden" name="id_instructor_reemplazo" value="">
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <button type="submit" class="button button-secondary" <?= $nextStatus === 'Inactivo' && $assignedCourses > 0 && $replacementInstructors === [] ? 'disabled' : '' ?>><?php
                                            if ($nextStatus === 'Activo') {
                                                echo 'Activar';
                                            } elseif ($assignedCourses > 0) {
                                                echo 'Reasignar cursos';
                                            } else {
                                                echo 'Desactivar';
                                            }
                                        ?></button>
                                    </form>
                                    <?php if ($instructor['estado_cuenta'] === 'Inactivo' && (int) $instructor['cursos_asignados'] > 0): ?>
                                        <form action="/instructores/estado" method="post" class="instructor-status-form instructor-action-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                            <input type="hidden" name="estado_cuenta" value="Inactivo">
                                            <div class="instructor-action-field">
                                                <?php if ($replacementInstructors !== []): ?>
                                                    <span class="instructor-field-note">Asigna <?= (int) $instructor['cursos_asignados'] ?> curso(s) a otro instructor; esta cuenta seguirá inactiva.</span>
                                                    <label class="visually-hidden" for="replace-inactive-<?= (int) $instructor['id_instructor'] ?>">Instructor que recibirá los cursos</label>
                                                    <select id="replace-inactive-<?= (int) $instructor['id_instructor'] ?>" name="id_instructor_reemplazo" required>
                                                        <option value="">Selecciona instructor</option>
                                                        <?php foreach ($replacementInstructors as $replacement): ?>
                                                            <option value="<?= (int) $replacement['id_instructor'] ?>"><?= e($replacement['apellidos'] . ', ' . $replacement['nombres']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <span class="instructor-field-note instructor-field-note--persistent">No hay otro instructor activo. Registra o activa uno para reasignar los cursos.</span>
                                                <?php endif; ?>
                                            </div>
                                            <button type="submit" class="button button-secondary" <?= $replacementInstructors === [] ? 'disabled' : '' ?>>Reasignar cursos</button>
                                        </form>
                                    <?php endif; ?>
                                        <details class="password-reset-details">
                                            <summary class="button button-secondary">Cambiar contraseña</summary>
                                            <form action="/instructores/contrasena" method="post" class="password-reset-form">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
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
                                        <form action="/instructores/eliminar" method="post" class="instructor-delete-form instructor-action-form" onsubmit="return confirm('<?= (int) $instructor['cursos_asignados'] > 0 ? 'Se reasignarán sus cursos y se eliminará la cuenta de instructor.' : 'Se eliminará la cuenta de instructor.' ?> Esta acción no se puede deshacer. ¿Deseas continuar?')">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                        <?php if ($assignedCourses > 0): ?>
                                            <div class="instructor-action-field">
                                                <?php if ($replacementInstructors !== []): ?>
                                                    <span class="instructor-field-note">Selecciona quién recibirá <?= $assignedCourses ?> curso(s) antes de eliminar la cuenta.</span>
                                                    <label class="visually-hidden" for="replace-delete-<?= (int) $instructor['id_instructor'] ?>">Instructor que recibirá los cursos antes de eliminar la cuenta</label>
                                                    <select id="replace-delete-<?= (int) $instructor['id_instructor'] ?>" name="id_instructor_reemplazo" required>
                                                        <option value="">Selecciona instructor</option>
                                                        <?php foreach ($replacementInstructors as $replacement): ?>
                                                            <option value="<?= (int) $replacement['id_instructor'] ?>"><?= e($replacement['apellidos'] . ', ' . $replacement['nombres']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <span class="instructor-field-note instructor-field-note--persistent">No hay otro instructor activo. Registra o activa uno antes de eliminar esta cuenta.</span>
                                                    <input type="hidden" name="id_instructor_reemplazo" value="">
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" name="id_instructor_reemplazo" value="">
                                        <?php endif; ?>
                                        <button type="submit" class="button button-secondary" <?= $assignedCourses > 0 && $replacementInstructors === [] ? 'disabled' : '' ?>>Eliminar cuenta</button>
                                    </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $instructors === [] ? '' : 'hidden' ?>><td colspan="7" class="empty-state">No hay instructores registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
