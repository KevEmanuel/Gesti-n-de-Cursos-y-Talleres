<?php

declare(strict_types=1);

/** Formulario y listado administrativo de horarios. */

$days = [
    'Lunes' => 'Lunes', 'Martes' => 'Martes', 'Miercoles' => 'Miércoles',
    'Jueves' => 'Jueves', 'Viernes' => 'Viernes', 'Sabado' => 'Sábado', 'Domingo' => 'Domingo',
];
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
                    <label>Curso<select name="id_curso" required><option value="">Selecciona</option>
                        <?php foreach ($courses as $course): ?><option value="<?= (int) $course['id_curso'] ?>"><?= e((string) $course['nombre_curso']) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Día<select name="dia" required><option value="">Selecciona</option>
                        <?php foreach ($days as $value => $label): ?><option value="<?= $value ?>"><?= $label ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Hora de inicio<input type="time" name="hora_inicio" required></label>
                    <label>Hora de finalización<input type="time" name="hora_finalizacion" required></label>
                    <button type="submit" class="button">Agregar horario</button>
                </form>
            <?php endif; ?>
        </section>
        <section class="card profile-list">
            <div class="toolbar"><h2>Horarios registrados</h2><label>Buscar<input type="search" placeholder="Buscar horarios" data-table-search></label></div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Curso</th><th>Día</th><th>Inicio</th><th>Finalización</th><th>Instructor</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td colspan="5">
                                    <form action="/horarios/actualizar" method="post" class="schedule-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_horario" value="<?= (int) $schedule['id_horario'] ?>">
                                        <label><span class="visually-hidden">Curso</span><select name="id_curso">
                                            <?php foreach ($courses as $course): ?><option value="<?= (int) $course['id_curso'] ?>" <?= (int) $schedule['id_curso'] === (int) $course['id_curso'] ? 'selected' : '' ?>><?= e((string) $course['nombre_curso']) ?></option><?php endforeach; ?>
                                        </select></label>
                                        <label><span class="visually-hidden">Día</span><select name="dia">
                                            <?php foreach ($days as $value => $label): ?><option value="<?= $value ?>" <?= $schedule['dia'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                                        </select></label>
                                        <label><span class="visually-hidden">Inicio</span><input type="time" name="hora_inicio" required value="<?= e(substr((string) $schedule['hora_inicio'], 0, 5)) ?>"></label>
                                        <label><span class="visually-hidden">Finalización</span><input type="time" name="hora_finalizacion" required value="<?= e(substr((string) $schedule['hora_finalizacion'], 0, 5)) ?>"></label>
                                        <span><?= e($schedule['apellidos'] . ', ' . $schedule['nombres']) ?></span>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td><form action="/horarios/eliminar" method="post" onsubmit="return confirm('¿Eliminar este horario?')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="id_horario" value="<?= (int) $schedule['id_horario'] ?>">
                                    <button type="submit" class="button button-secondary">Eliminar</button>
                                </form></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $schedules === [] ? '' : 'hidden' ?>><td colspan="6" class="empty-state">No hay horarios registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
