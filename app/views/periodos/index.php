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
            <?php if ($courses === []): ?>
                <p>Primero registra un curso activo o cerrado.</p>
            <?php else: ?>
                <form action="/periodos/crear" method="post" class="period-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <label>Curso<select name="id_curso" required><option value="">Selecciona</option>
                        <?php foreach ($courses as $course): ?><option value="<?= (int) $course['id_curso'] ?>"><?= e((string) $course['nombre_curso']) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Fecha de inicio<input type="date" name="fecha_inicio" required></label>
                    <label>Fecha de cierre<input type="date" name="fecha_cierre" required></label>
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
                                        <input type="hidden" name="id_periodo" value="<?= (int) $period['id_periodo'] ?>">
                                        <label><span class="visually-hidden">Curso</span><select name="id_curso">
                                            <?php foreach ($courses as $course): ?><option value="<?= (int) $course['id_curso'] ?>" <?= (int) $period['id_curso'] === (int) $course['id_curso'] ? 'selected' : '' ?>><?= e((string) $course['nombre_curso']) ?></option><?php endforeach; ?>
                                        </select></label>
                                        <label><span class="visually-hidden">Inicio</span><input type="date" name="fecha_inicio" required value="<?= e((string) $period['fecha_inicio']) ?>"></label>
                                        <label><span class="visually-hidden">Cierre</span><input type="date" name="fecha_cierre" required value="<?= e((string) $period['fecha_cierre']) ?>"></label>
                                        <span><?= e((string) $period['estado_periodo']) ?></span>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td><form action="/periodos/eliminar" method="post" onsubmit="return confirm('¿Eliminar este período?')">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="id_periodo" value="<?= (int) $period['id_periodo'] ?>">
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
