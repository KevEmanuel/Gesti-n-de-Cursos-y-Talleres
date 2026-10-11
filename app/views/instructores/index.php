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
            <form action="/instructores/crear" method="post" class="profile-create-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Correo<input type="email" name="correo" maxlength="254" required value="<?= e($old['correo'] ?? '') ?>"></label>
                <label>Nombres<input name="nombres" maxlength="100" required value="<?= e($old['nombres'] ?? '') ?>"></label>
                <label>Apellidos<input name="apellidos" maxlength="100" required value="<?= e($old['apellidos'] ?? '') ?>"></label>
                <label>Teléfono<input name="telefono" maxlength="30" value="<?= e($old['telefono'] ?? '') ?>"></label>
                <label>Especialidad<input name="especialidad" maxlength="255" value="<?= e($old['especialidad'] ?? '') ?>"></label>
                <label>Contraseña inicial (mínimo 12 caracteres)<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
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
                            <tr>
                                <td><?= e((string) $instructor['carnet']) ?></td>
                                <td colspan="5">
                                    <form action="/instructores/actualizar" method="post" class="profile-edit-form instructor-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                        <label><span class="visually-hidden">Nombres</span><input name="nombres" maxlength="100" required value="<?= e((string) $instructor['nombres']) ?>"></label>
                                        <label><span class="visually-hidden">Apellidos</span><input name="apellidos" maxlength="100" required value="<?= e((string) $instructor['apellidos']) ?>"></label>
                                        <label><span class="visually-hidden">Correo</span><input type="email" name="correo" maxlength="254" required value="<?= e((string) $instructor['correo']) ?>"></label>
                                        <label><span class="visually-hidden">Teléfono</span><input name="telefono" maxlength="30" value="<?= e((string) ($instructor['telefono'] ?? '')) ?>"></label>
                                        <label><span class="visually-hidden">Especialidad</span><input name="especialidad" maxlength="255" value="<?= e((string) ($instructor['especialidad'] ?? '')) ?>"></label>
                                        <span><?= e((string) $instructor['estado_cuenta']) ?></span>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td>
                                    <form action="/instructores/estado" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                        <?php $nextStatus = $instructor['estado_cuenta'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>
                                        <input type="hidden" name="estado_cuenta" value="<?= $nextStatus ?>">
                                        <?php if ($nextStatus === 'Inactivo' && (int) $instructor['cursos_asignados'] > 0): ?>
                                            <label>Instructor que recibirá el <?= (int) $instructor['cursos_asignados'] ?> curso; la cuenta quedará inactiva
                                                <select name="id_instructor_reemplazo" required>
                                                    <option value="">Selecciona instructor activo</option>
                                                    <?php foreach ($instructors as $replacement): ?>
                                                        <?php if ((int) $replacement['id_instructor'] !== (int) $instructor['id_instructor'] && $replacement['estado_cuenta'] === 'Activo'): ?>
                                                            <option value="<?= (int) $replacement['id_instructor'] ?>"><?= e($replacement['apellidos'] . ', ' . $replacement['nombres']) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                        <?php endif; ?>
                                        <button type="submit" class="button button-secondary"><?php
                                            if ($nextStatus === 'Activo') {
                                                echo 'Activar';
                                            } elseif ((int) $instructor['cursos_asignados'] > 0) {
                                                echo 'Reasignar cursos';
                                            } else {
                                                echo 'Desactivar';
                                            }
                                        ?></button>
                                    </form>
                                    <?php if ($instructor['estado_cuenta'] === 'Inactivo' && (int) $instructor['cursos_asignados'] > 0): ?>
                                        <form action="/instructores/estado" method="post" class="instructor-status-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                            <input type="hidden" name="estado_cuenta" value="Inactivo">
                                            <label>Instructor que recibira el <?= (int) $instructor['cursos_asignados'] ?> curso; la cuenta seguirá inactiva
                                                <select name="id_instructor_reemplazo" required>
                                                    <option value="">Selecciona instructor activo</option>
                                                    <?php foreach ($instructors as $replacement): ?>
                                                        <?php if ((int) $replacement['id_instructor'] !== (int) $instructor['id_instructor'] && $replacement['estado_cuenta'] === 'Activo'): ?>
                                                            <option value="<?= (int) $replacement['id_instructor'] ?>"><?= e($replacement['apellidos'] . ', ' . $replacement['nombres']) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <button type="submit" class="button button-secondary">Reasignar sin eliminar la cuenta</button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="/instructores/eliminar" method="post" class="instructor-delete-form" onsubmit="return confirm('<?= (int) $instructor['cursos_asignados'] > 0 ? 'Se reasignarán sus cursos y se eliminará la cuenta de instructor.' : 'Se eliminará la cuenta de instructor.' ?> Esta acción no se puede deshacer. ¿Deseas continuar?')">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_instructor" value="<?= (int) $instructor['id_instructor'] ?>">
                                        <?php if ((int) $instructor['cursos_asignados'] > 0): ?>
                                            <label>Instructor que recibirá los <?= (int) $instructor['cursos_asignados'] ?> curso(s) antes de eliminar la cuenta
                                                <select name="id_instructor_reemplazo" required>
                                                    <option value="">Selecciona instructor activo</option>
                                                    <?php foreach ($instructors as $replacement): ?>
                                                        <?php if ((int) $replacement['id_instructor'] !== (int) $instructor['id_instructor'] && $replacement['estado_cuenta'] === 'Activo'): ?>
                                                            <option value="<?= (int) $replacement['id_instructor'] ?>"><?= e($replacement['apellidos'] . ', ' . $replacement['nombres']) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                        <?php else: ?>
                                            <input type="hidden" name="id_instructor_reemplazo" value="">
                                        <?php endif; ?>
                                        <button type="submit" class="button button-secondary"><?=
                                            (int) $instructor['cursos_asignados'] > 0
                                                ? 'eliminar cuenta'
                                                : 'Eliminar cuenta'
                                        ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $instructors === [] ? '' : 'hidden' ?>><td colspan="7" class="empty-state">No hay instructores registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
