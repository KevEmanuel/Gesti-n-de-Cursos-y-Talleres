<?php

declare(strict_types=1);

/** Filtros y resultados de reportes de cursos. */

?>
<main class="container">
        <header class="page-header"><div><a href="/">Volver al dashboard</a><h1><?= e($title) ?></h1></div></header>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <section class="card">
            <h2>Filtrar ocupación</h2>
            <form action="/reportes" method="get" class="report-filter">
                <label>Desde<input type="date" name="fecha_inicio" value="<?= e($filters['fecha_inicio']) ?>"></label>
                <label>Hasta<input type="date" name="fecha_cierre" value="<?= e($filters['fecha_cierre']) ?>"></label>
                <label>Categoría<select name="id_categoria"><option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id_categoria'] ?>" <?= $filters['id_categoria'] === (string) $category['id_categoria'] ? 'selected' : '' ?>><?= e((string) $category['nombre']) ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Modalidad<select name="modalidad"><option value="">Todas</option>
                    <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                        <option value="<?= $modality ?>" <?= $filters['modalidad'] === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
                    <?php endforeach; ?>
                </select></label>
                <button class="button" type="submit">Aplicar filtros</button>
            </form>
        </section>
        <section class="card profile-list">
            <h2>Ocupación por curso</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Curso</th><th>Categoría</th><th>Modalidad</th><th>Estado</th><th>Inscritos activos</th><th>Capacidad</th><th>Cupos disponibles</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e((string) $row['nombre_curso']) ?></td>
                                <td><?= e((string) $row['categoria']) ?></td>
                                <td><?= e((string) $row['modalidad']) ?></td>
                                <?php $courseStatus = (int) $row['cupos_disponibles'] === 0 ? 'Lleno' : (string) $row['estado']; ?>
                                <td><span class="course-status course-status--<?= e(strtolower($courseStatus)) ?>"><?= e($courseStatus) ?></span></td>
                                <td><?= (int) $row['inscritos_activos'] ?></td>
                                <td><?= (int) $row['maximo_cupos'] ?></td>
                                <td><?= (int) $row['cupos_disponibles'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($rows === []): ?><tr><td colspan="7" class="empty-state">No hay resultados para los filtros seleccionados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
