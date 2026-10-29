<?php

declare(strict_types=1);

/** Consolidacion de los datos generados
 * registros de alumnos, cursos etc. */

?>
<main class="container">
        <header class="page-header"><div><a href="/">Volver al fdashboard</a><h1><?= e($title) ?></h1></div></header>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <section class="card">
            <h2>Busqueda de Periodo</h2>
            <form action="/reportes" method="get" class="report-filter">
                <label>Desde<input type="date" name="FechaInicio" value="<?= e($filters['FechaInicio']) ?>"></label>
                <label>Hasta<input type="date" name="FechaCierre" value="<?= e($filters['FechaCierre']) ?>"></label>
                <label>Categoría<select name="IdCategoria"><option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['IdCategoria'] ?>" <?= $filters['IdCategoria'] === (string) $category['IdCategoria'] ? 'selected' : '' ?>><?= e((string) $category['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Modalidad<select name="Modalidad"><option value="">Todas</option>
                    <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                        <option value="<?= $modality ?>" <?= $filters['Modalidad'] === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
                    <?php endforeach; ?>
                </select></label>
                <button class="button" type="submit">Aplicar filtros</button>
            </form>
        </section>
        <section class="card profile-list">
            <div class="report-results-heading">
                <h2>Detalle de los Registros</h2>
                <form action="/reportes/pdf" method="get">
                    <?php foreach ($filters as $filterName => $filterValue): ?>
                        <input type="hidden" name="<?= e($filterName) ?>" value="<?= e($filterValue) ?>">
                    <?php endforeach; ?>
                    <button class="button button-secondary" type="submit" <?= $error !== null ? 'disabled' : '' ?>>Exportar PDF</button>
                </form>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Curso</th><th>Categoría</th><th>Modalidad</th><th>Estado</th><th>Inscritos activos</th><th>Capacidad</th><th>Cupos disponibles</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e((string) $row['Nombre_Curso']) ?></td>
                                <td><?= e((string) $row['categoria']) ?></td>
                                <td><?= e((string) $row['Modalidad']) ?></td>
                                <?php $courseStatus = (int) $row['Cupos_Disponibles'] === 0 ? 'Lleno' : (string) $row['Estado']; ?>
                                <td><span class="course-status course-status--<?= e(strtolower($courseStatus)) ?>"><?= e($courseStatus) ?></span></td>
                                <td><?= (int) $row['InscritosActivos'] ?></td>
                                <td><?= (int) $row['Maximo_cupos'] ?></td>
                                <td><?= (int) $row['Cupos_Disponibles'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($rows === []): ?><tr><td colspan="7" class="empty-state">No hay resultados para los filtros seleccionados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
