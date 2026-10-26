<?php

declare(strict_types=1);

/** Formulario y listado administrativo de solicitudes de inscripcion. */

?>
<main class="container">
        <header class="page-header"><div><a href="/">Volver al dashboard</a><h1><?= e($title) ?></h1></div></header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <section class="card">
            <h2>Registrar solicitud</h2>
            <?php if ($students === [] || $courses === []): ?>
                <p>Se requiere un estudiante activo, un curso activo con cupos y un período de inscripción vigente.</p>
            <?php else: ?>
                <form action="/solicitudes/crear" method="post" class="request-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <label>Estudiante<select name="id_estudiante" required><option value="">Selecciona</option>
                        <?php foreach ($students as $student): ?><option value="<?= (int) $student['id_estudiante'] ?>"><?= e($student['carnet'] . ' · ' . $student['apellidos'] . ', ' . $student['nombres']) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Curso<select name="id_curso" required><option value="">Selecciona</option>
                        <?php foreach ($courses as $course): ?><option value="<?= (int) $course['id_curso'] ?>"><?= e($course['nombre_curso'] . ' · cupos: ' . $course['cupos_disponibles']) ?></option><?php endforeach; ?>
                    </select></label>
                    <button type="submit" class="button">Registrar y reservar cupo</button>
                </form>
            <?php endif; ?>
        </section>
        <section class="card profile-list">
            <div class="toolbar"><h2>Solicitudes registradas</h2><label>Buscar<input type="search" placeholder="Buscar solicitudes" data-table-search></label></div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Estudiante</th><th>Carnet</th><th>Correo</th><th>Curso</th><th>Fecha</th><th>Estado</th><th>Motivo de rechazo</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= e($request['apellidos'] . ', ' . $request['nombres']) ?></td>
                                <td><?= e((string) $request['carnet']) ?></td>
                                <td><?= e((string) $request['correo']) ?></td>
                                <td><?= e((string) $request['nombre_curso']) ?></td>
                                <td><?= e((string) $request['fecha_inscripcion']) ?></td>
                                <td><span class="request-status request-status--<?= e(strtolower((string) $request['estado_inscripcion'])) ?>"><?= e((string) $request['estado_inscripcion']) ?></span></td>
                                <td><?= e((string) ($request['motivo_rechazo'] ?? '—')) ?></td>
                                <td>
                                    <form action="/solicitudes/estado" method="post" class="enrollment-status-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_inscripcion" value="<?= (int) $request['id_inscripcion'] ?>">
                                        <label>Estado
                                            <select name="estado_inscripcion" data-rejection-reason-select>
                                                <?php foreach (['Pendiente', 'Confirmada', 'Rechazada', 'Cancelada'] as $status): ?>
                                                    <option value="<?= $status ?>" <?= $request['estado_inscripcion'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label data-rejection-reason-wrapper <?= $request['estado_inscripcion'] === 'Rechazada' ? '' : 'hidden' ?>>Motivo
                                            <textarea name="motivo_rechazo" maxlength="1000" rows="2" placeholder="Explica brevemente el motivo del rechazo" data-rejection-reason <?= $request['estado_inscripcion'] === 'Rechazada' ? 'required' : '' ?>><?= e((string) ($request['motivo_rechazo'] ?? '')) ?></textarea>
                                        </label>
                                        <button type="submit" class="button">Guardar estado</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($requests === []): ?><tr><td colspan="8" class="empty-state">No hay solicitudes registradas.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
