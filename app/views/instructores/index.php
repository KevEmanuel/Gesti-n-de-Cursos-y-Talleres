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
            <h2>Registrar un nuevo Instructor</h2>
            <p><strong>El Carnet</strong> se genera automáticamente con las iniciales de los Apellidos y 4 numeros Aleatorios.</p>
            <p><strong>Nota:</strong> Para reasignar cursos al <strong>desactivar</strong> o <strong>eliminar</strong> un Instructor debe haber otro <strong>instructor activo</strong></p>
            <form action="/instructores/crear" method="post" class="profile-create-form instructor-create-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo electrónico<input type="email" name="Correo" maxlength="254" required autocomplete="email" placeholder="Ejemplo: Nombre@dominio.com" value="<?= e($old['Correo'] ?? '') ?>"></label>
                <label>Nombres<input name="Nombres" maxlength="100" required autocomplete="given-name" placeholder="Ejemplo: Ana María" value="<?= e($old['Nombres'] ?? '') ?>"></label>
                <label>Apellidos<input name="Apellidos" maxlength="100" required autocomplete="family-name" placeholder="Ej. López García" value="<?= e($old['Apellidos'] ?? '') ?>"></label>
          
                <label>Teléfono celular<input type="tel" name="Telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." placeholder="Ej. 71234567" value="<?= e($old['Telefono'] ?? '') ?>"></label>
                <label class="specialty-field">Especialidad<input class="specialty-input" name="Especialidad" maxlength="255" placeholder="Ej. Diseño gráfico" value="<?= e($old['Especialidad'] ?? '') ?>"></label>
                <label>Contraseña<input type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="La contraseña debe tener 12 caracteres o más"></label>               
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
                    <thead><tr><th colspan="7">Fichas de instructores</th></tr></thead>
                    <tbody>
                        <?php foreach ($instructors as $instructor): ?>
                            <?php
                            $assignedCourses = (int) $instructor['cursos_asignados'];
                            $replacementInstructors = array_values(array_filter(
                                $instructors,
                                static fn (array $replacement): bool =>
                                    (int) $replacement['IdInstructor'] !== (int) $instructor['IdInstructor']
                                    && $replacement['Estado_Cuenta'] === 'Activo'
                            ));
                            ?>
                            <tr class="instructor-record-row">
                                <td colspan="7">
                                    <article class="instructor-card">
                                        <header class="instructor-card-header">
                                            <span class="profile-carnet"><?= e((string) $instructor['Carnet']) ?></span>
                                            <span class="instructor-card-title"><?= e($instructor['Nombres'] . ' ' . $instructor['Apellidos']) ?></span>
                                            <span class="account-status <?= $instructor['Estado_Cuenta'] === 'Activo' ? 'account-status--active' : 'account-status--inactive' ?>"><?= e((string) $instructor['Estado_Cuenta']) ?></span>
                                            <aside class="instructor-row-actions" aria-label="Acciones para <?= e($instructor['Nombres'] . ' ' . $instructor['Apellidos']) ?>">
                                                <details class="instructor-actions-menu">
                                                    <summary aria-label="Acciones para <?= e($instructor['Nombres'] . ' ' . $instructor['Apellidos']) ?>" aria-haspopup="true">
                                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                                                        </svg>
                                                        <span>Acciones</span>
                                                    </summary>
                                                    <div class="instructor-actions-panel">
                                    <form action="/instructores/estado" method="post" class="instructor-action-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="IdInstructor" value="<?= (int) $instructor['IdInstructor'] ?>">
                                        <?php $nextStatus = $instructor['Estado_Cuenta'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>
                                        <input type="hidden" name="Estado_Cuenta" value="<?= $nextStatus ?>">
                                        <?php if ($nextStatus === 'Inactivo' && $assignedCourses > 0): ?>
                                            <div class="instructor-action-field">
                                                <?php if ($replacementInstructors !== []): ?>
                                                    <span class="instructor-field-note">Selecciona quién recibirá <?= $assignedCourses ?> curso(s). La cuenta quedará inactiva.</span>
                                                    <label class="visually-hidden" for="replace-status-<?= (int) $instructor['IdInstructor'] ?>">Instructor que recibirá los cursos</label>
                                                    <select id="replace-status-<?= (int) $instructor['IdInstructor'] ?>" name="id_instructor_reemplazo" required>
                                                        <option value="">Selecciona instructor</option>
                                                        <?php foreach ($replacementInstructors as $replacement): ?>
                                                            <option value="<?= (int) $replacement['IdInstructor'] ?>"><?= e($replacement['Apellidos'] . ', ' . $replacement['Nombres']) ?></option>
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
                                    <?php if ($instructor['Estado_Cuenta'] === 'Inactivo' && (int) $instructor['cursos_asignados'] > 0): ?>
                                        <form action="/instructores/estado" method="post" class="instructor-status-form instructor-action-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="IdInstructor" value="<?= (int) $instructor['IdInstructor'] ?>">
                                            <input type="hidden" name="Estado_Cuenta" value="Inactivo">
                                            <div class="instructor-action-field">
                                                <?php if ($replacementInstructors !== []): ?>
                                                    <span class="instructor-field-note">Asigna <?= (int) $instructor['cursos_asignados'] ?> curso(s) a otro instructor; esta cuenta seguirá inactiva.</span>
                                                    <label class="visually-hidden" for="replace-inactive-<?= (int) $instructor['IdInstructor'] ?>">Instructor que recibirá los cursos</label>
                                                    <select id="replace-inactive-<?= (int) $instructor['IdInstructor'] ?>" name="id_instructor_reemplazo" required>
                                                        <option value="">Selecciona instructor</option>
                                                        <?php foreach ($replacementInstructors as $replacement): ?>
                                                            <option value="<?= (int) $replacement['IdInstructor'] ?>"><?= e($replacement['Apellidos'] . ', ' . $replacement['Nombres']) ?></option>
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
                                                <input type="hidden" name="IdInstructor" value="<?= (int) $instructor['IdInstructor'] ?>">
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
                                        <input type="hidden" name="IdInstructor" value="<?= (int) $instructor['IdInstructor'] ?>">
                                        <?php if ($assignedCourses > 0): ?>
                                            <div class="instructor-action-field">
                                                <?php if ($replacementInstructors !== []): ?>
                                                    <span class="instructor-field-note">Selecciona quién recibirá <?= $assignedCourses ?> curso(s) antes de eliminar la cuenta.</span>
                                                    <label class="visually-hidden" for="replace-delete-<?= (int) $instructor['IdInstructor'] ?>">Instructor que recibirá los cursos antes de eliminar la cuenta</label>
                                                    <select id="replace-delete-<?= (int) $instructor['IdInstructor'] ?>" name="id_instructor_reemplazo" required>
                                                        <option value="">Selecciona instructor</option>
                                                        <?php foreach ($replacementInstructors as $replacement): ?>
                                                            <option value="<?= (int) $replacement['IdInstructor'] ?>"><?= e($replacement['Apellidos'] . ', ' . $replacement['Nombres']) ?></option>
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
                                                </details>
                                            </aside>
                                        </header>
                                        <div class="instructor-card-content">
                                            <form action="/instructores/actualizar" method="post" class="profile-edit-form instructor-edit-form">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="IdInstructor" value="<?= (int) $instructor['IdInstructor'] ?>">
                                                <div class="instructor-edit-fields">
                                                    <label>Nombres<input name="Nombres" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $instructor['Nombres']) ?>"></label>
                                                    <label>Apellidos<input name="Apellidos" maxlength="100" required title="Usa letras, espacios, guiones o apóstrofes." value="<?= e((string) $instructor['Apellidos']) ?>"></label>
                                                    <label>Correo electrónico<input type="email" name="Correo" maxlength="254" required value="<?= e((string) $instructor['Correo']) ?>"></label>
                                                    <label>Teléfono celular<input type="tel" name="Telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Opcional. Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." value="<?= e((string) ($instructor['Telefono'] ?? '')) ?>"></label>
                                                    <label class="specialty-field">Especialidad<input class="specialty-input" name="Especialidad" maxlength="255" placeholder="Ej. Diseño gráfico" title="Opcional; si la completas, incluye al menos una letra." value="<?= e((string) ($instructor['Especialidad'] ?? '')) ?>"></label>
                                                </div>
                                                <div class="instructor-edit-footer">
                                                    <button type="submit" class="button">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </article>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $instructors === [] ? '' : 'hidden' ?>><td colspan="7" class="empty-state">No hay instructores registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
