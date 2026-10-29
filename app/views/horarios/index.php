<?php

declare(strict_types=1);

/** Formulario y listado administrativo de horarios. */

$days = [
    'Lunes' => 'Lunes', 'Martes' => 'Martes', 'Miercoles' => 'Miércoles',
    'Jueves' => 'Jueves', 'Viernes' => 'Viernes', 'Sabado' => 'Sábado', 'Domingo' => 'Domingo',
];
$schedulesByCourse = [];
foreach ($schedules as $schedule) {
    $schedulesByCourse[(int) $schedule['IdCurso']][] = $schedule;
}
?>
<main class="container">
        <header class="page-header"><div><a href="/">Volver al dashboard</a><h1><?= e($title) ?></h1></div></header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <section class="card">
            <h2>Agregar horario</h2>
            <?php if ($courses === []): ?>
                <p>Primero registra un curso activo o cerrado.</p>
            <?php else: ?>
                <form action="/horarios/crear" method="post" class="schedule-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <label>Curso<select name="IdCurso" required><option value="">Selecciona</option>
                        <?php foreach ($courses as $course): ?><option value="<?= (int) $course['IdCurso'] ?>"><?= e((string) $course['Nombre_Curso']) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Día<select name="Dia" required><option value="">Selecciona</option>
                        <?php foreach ($days as $value => $label): ?><option value="<?= $value ?>"><?= $label ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Hora de inicio<input type="time" name="HoraInicio" step="60" required></label>
                    <label>Hora de finalización<input type="time" name="Hora_finalizacion" step="60" required></label>
                    <button type="submit" class="button">Agregar horario</button>
                </form>
            <?php endif; ?>
        </section>
        <section class="card profile-list">
            <div class="toolbar"><h2>Horarios registrados</h2><label>Buscar<input type="search" placeholder="Buscar horarios" data-table-search></label></div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Horarios organizados por curso</th></tr></thead>
                    <tbody>
                        <?php foreach ($schedulesByCourse as $courseSchedules): ?>
                            <?php $courseSchedule = $courseSchedules[0]; ?>
                            <tr class="schedule-course-row">
                                <td>
                                    <section class="schedule-course-card">
                                        <header class="schedule-course-header">
                                            <div>
                                                <span class="schedule-course-eyebrow">Curso</span>
                                                <h3><?= e((string) $courseSchedule['Nombre_Curso']) ?></h3>
                                            </div>
                                            <span class="schedule-course-instructor"><?= e($courseSchedule['Apellidos'] . ', ' . $courseSchedule['Nombres']) ?></span>
                                        </header>
                                        <div class="schedule-course-list">
                                            <?php foreach ($courseSchedules as $schedule): ?>
                                                <div class="schedule-entry">
                                                    <form id="schedule-update-<?= (int) $schedule['IdHorario'] ?>" action="/horarios/actualizar" method="post" class="schedule-edit-form">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                        <input type="hidden" name="IdHorario" value="<?= (int) $schedule['IdHorario'] ?>">
                                                        <label>Curso<select name="IdCurso" required>
                                                            <?php foreach ($courses as $course): ?><option value="<?= (int) $course['IdCurso'] ?>" <?= (int) $schedule['IdCurso'] === (int) $course['IdCurso'] ? 'selected' : '' ?>><?= e((string) $course['Nombre_Curso']) ?></option><?php endforeach; ?>
                                                        </select></label>
                                                        <label>Día<select name="Dia" required>
                                                            <?php foreach ($days as $value => $label): ?><option value="<?= $value ?>" <?= $schedule['Dia'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                                                        </select></label>
                                                        <label>Hora de inicio<input type="time" name="HoraInicio" step="60" required title="Usa formato de 24 horas." value="<?= e(substr((string) $schedule['HoraInicio'], 0, 5)) ?>"></label>
                                                        <label>Hora de finalización<input type="time" name="Hora_finalizacion" step="60" required title="Usa formato de 24 horas y una hora posterior al inicio." value="<?= e(substr((string) $schedule['Hora_finalizacion'], 0, 5)) ?>"></label>
                                                    </form>
                                                    <details class="schedule-actions-menu">
                                                        <summary aria-label="Acciones para el horario de <?= e($schedule['Dia']) ?>" aria-haspopup="true">
                                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                                <path d="M4 6h16M4 12h16M4 18h16"></path>
                                                            </svg>
                                                            <span>Acciones</span>
                                                        </summary>
                                                        <div class="schedule-actions-panel">
                                                            <button type="submit" form="schedule-update-<?= (int) $schedule['IdHorario'] ?>" class="button">Guardar cambios</button>
                                                            <form action="/horarios/eliminar" method="post" class="schedule-delete-form" onsubmit="return confirm('¿Eliminar este horario?')">
                                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                                <input type="hidden" name="IdHorario" value="<?= (int) $schedule['IdHorario'] ?>">
                                                                <button type="submit" class="button button-secondary">Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </details>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $schedules === [] ? '' : 'hidden' ?>><td class="empty-state">No hay horarios registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
