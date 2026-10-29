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
                    <label>Estudiante<select name="IdEstudiante" required><option value="">Selecciona</option>
                        <?php foreach ($students as $student): ?><option value="<?= (int) $student['IdEstudiante'] ?>"><?= e($student['Carnet'] . ' · ' . $student['Apellidos'] . ', ' . $student['Nombres']) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Curso<select name="IdCurso" required><option value="">Selecciona</option>
                        <?php foreach ($courses as $course): ?><option value="<?= (int) $course['IdCurso'] ?>"><?= e($course['Nombre_Curso'] . ' · cupos: ' . $course['Cupos_Disponibles']) ?></option><?php endforeach; ?>
                    </select></label>
                    <button type="submit" class="button">Registrar y reservar cupo</button>
                </form>
            <?php endif; ?>
        </section>
        <section class="card profile-list">
            <div class="toolbar"><h2>Solicitudes registradas</h2><label>Buscar<input type="search" placeholder="Buscar solicitudes" data-table-search></label></div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Solicitudes de inscripción</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr class="enrollment-request-row">
                                <td>
                                    <article class="enrollment-request-card">
                                        <header class="enrollment-request-header">
                                            <div>
                                                <span class="enrollment-request-eyebrow">Estudiante</span>
                                                <h3><?= e($request['Apellidos'] . ', ' . $request['Nombres']) ?></h3>
                                            </div>
                                            <span class="request-status request-status--<?= e(strtolower((string) $request['EstadoInscripcion'])) ?>"><?= e((string) $request['EstadoInscripcion']) ?></span>
                                        </header>
                                        <div class="enrollment-request-details">
                                            <div><span>Carnet</span><strong><?= e((string) $request['Carnet']) ?></strong></div>
                                            <div><span>Correo</span><strong><?= e((string) $request['Correo']) ?></strong></div>
                                            <div><span>Curso</span><strong><?= e((string) $request['Nombre_Curso']) ?></strong></div>
                                            <div><span>Fecha de solicitud</span><strong><?= e((string) $request['FechaInscripcion']) ?></strong></div>
                                        </div>
                                        <?php if ($request['MotivoRechazo'] !== null && $request['MotivoRechazo'] !== ''): ?>
                                            <div class="enrollment-rejection-note"><strong>Motivo de rechazo</strong><p><?= e((string) $request['MotivoRechazo']) ?></p></div>
                                        <?php endif; ?>
                                        <details class="enrollment-actions-menu">
                                            <summary aria-label="Acciones para la solicitud de <?= e($request['Nombres'] . ' ' . $request['Apellidos']) ?>" aria-haspopup="true">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                    <path d="M4 6h16M4 12h16M4 18h16"></path>
                                                </svg>
                                                <span>Acciones</span>
                                            </summary>
                                            <div class="enrollment-actions-panel">
                                                <form action="/solicitudes/estado" method="post" class="enrollment-status-form">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <input type="hidden" name="IdInscripcion" value="<?= (int) $request['IdInscripcion'] ?>">
                                                    <label>Estado
                                                        <select name="EstadoInscripcion" data-rejection-reason-select>
                                                            <?php foreach (['Pendiente', 'Confirmada', 'Rechazada', 'Cancelada'] as $status): ?>
                                                                <option value="<?= $status ?>" <?= $request['EstadoInscripcion'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </label>
                                                    <label data-rejection-reason-wrapper <?= $request['EstadoInscripcion'] === 'Rechazada' ? '' : 'hidden' ?>>Motivo de rechazo
                                                        <textarea name="MotivoRechazo" maxlength="1000" rows="3" placeholder="Explica brevemente el motivo del rechazo" data-rejection-reason <?= $request['EstadoInscripcion'] === 'Rechazada' ? 'required' : '' ?>><?= e((string) ($request['MotivoRechazo'] ?? '')) ?></textarea>
                                                    </label>
                                                    <button type="submit" class="button">Guardar cambios</button>
                                                </form>
                                            </div>
                                        </details>
                                    </article>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($requests === []): ?><tr><td class="empty-state">No hay solicitudes registradas.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
