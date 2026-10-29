<?php

declare(strict_types=1);

/** Formulario y listado administrativo de periodos de inscripcion. */

?>
<main class="container">
        <header class="page-header"><div><a href="/">Volver al dashboard</a><h1><?= e($title) ?></h1></div></header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <section class="card">
            <h2>Crear período</h2>
            <p><strong>Nota:  La fecha de inicio puede ser igual a la fecha de cierre pero no debe ser mayor a la fecha de cierre</strong></p>
            <?php if ($courses === []): ?>
                <p>Primero registra un curso activo o cerrado.</p>
            <?php else: ?>
                <form action="/periodos/crear" method="post" class="period-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <label>Curso<select name="IdCurso" required><option value="">Selecciona un curso</option>
                        <?php foreach ($courses as $course): ?><option value="<?= (int) $course['IdCurso'] ?>" <?= (string) ($old['IdCurso'] ?? '') === (string) $course['IdCurso'] ? 'selected' : '' ?>><?= e((string) $course['Nombre_Curso']) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Fecha de inicio<input type="date" name="FechaInicio" required value="<?= e($old['FechaInicio'] ?? '') ?>"></label>
                    <label>Fecha de cierre<input type="date" name="FechaCierre" required value="<?= e($old['FechaCierre'] ?? '') ?>"></label>
                    <button type="submit" class="button">Crear período</button>
                </form>
            <?php endif; ?>
        </section>
        <section class="card profile-list">
            <div class="toolbar"><h2>Períodos registrados</h2><label>Buscar<input type="search" placeholder="Buscar períodos" data-table-search></label></div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Curso</th><th>Inicio</th><th>Cierre</th><th>Estado calculado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($periods as $period): ?>
                            <tr>
                                <td colspan="4">
                                    <form action="/periodos/actualizar" method="post" class="period-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="IdPeriodo" value="<?= (int) $period['IdPeriodo'] ?>">
                                        <label><span class="visually-hidden">Curso</span><select name="IdCurso">
                                            <?php foreach ($courses as $course): ?><option value="<?= (int) $course['IdCurso'] ?>" <?= (int) $period['IdCurso'] === (int) $course['IdCurso'] ? 'selected' : '' ?>><?= e((string) $course['Nombre_Curso']) ?></option><?php endforeach; ?>
                                        </select></label>
                                        <label><span class="visually-hidden">Inicio</span><input type="date" name="FechaInicio" required value="<?= e((string) $period['FechaInicio']) ?>"></label>
                                        <label><span class="visually-hidden">Cierre</span><input type="date" name="FechaCierre" required value="<?= e((string) $period['FechaCierre']) ?>"></label>
                                        <span class="period-status period-status--<?= e(strtolower((string) $period['estado_periodo'])) ?>"><?= e((string) $period['estado_periodo']) ?></span>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td><form action="/periodos/eliminar" method="post" onsubmit="return confirm('¿Eliminar este período?')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="IdPeriodo" value="<?= (int) $period['IdPeriodo'] ?>">
                                    <button type="submit" class="button button-secondary">Eliminar</button>
                                </form></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $periods === [] ? '' : 'hidden' ?>><td colspan="5" class="empty-state">No hay períodos registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
