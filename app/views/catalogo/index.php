<?php

declare(strict_types=1);

/** Listado publico de cursos con filtros de busqueda. */

?>

<main class="container catalog-page">
    <?php require __DIR__ . '/../shared/public-navigation.php'; ?>
    <header class="page-header catalog-heading">
        <div><p class="eyebrow">FORMACIÓN PARA TU FUTURO</p><h1><?= e($title) ?></h1><p>Busca por curso, categoría, Modalidad o instructor.</p></div>
    </header>

        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>

        <form action="/catalogo" method="get" class="catalog-filter card" id="catalog-filter">
            <label>Buscar<input type="search" name="busqueda" maxlength="150" value="<?= e($filters['busqueda']) ?>" placeholder="Nombre, categoría o instructor"></label>
            <label>Categoría
                <select name="IdCategoria">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['IdCategoria'] ?>" <?= $filters['IdCategoria'] === (string) $category['IdCategoria'] ? 'selected' : '' ?>><?= e((string) $category['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Modalidad
                <select name="Modalidad">
                    <option value="">Todas</option>
                    <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                        <option value="<?= $modality ?>" <?= $filters['Modalidad'] === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="filter-actions"><button class="button" type="submit">Aplicar filtros</button><a href="/catalogo">Limpiar</a></div>
        </form>

        <section class="catalog-grid" aria-label="Cursos disponibles">
            <?php foreach ($courses as $course): ?>
                <article class="course-card">
                    <div class="course-card-top">
                        <span class="course-category"><?= e((string) $course['categoria']) ?></span>
                        <?php
                        $isFull = $course['Estado'] === 'Lleno' || (int) $course['Cupos_Disponibles'] === 0;
                        $hasPeriod = $course['periodo_inicio'] !== null;
                        ?>
                        <span class="badge <?= $isFull || !$hasPeriod ? 'badge-muted' : 'badge-available' ?>">
                            <?= $isFull ? 'Cupos agotados' : ($hasPeriod ? 'Inscripción abierta' : 'Sin período vigente') ?>
                        </span>
                    </div>
                    <h2><?= e((string) $course['Nombre_Curso']) ?></h2>
                    <p><?= e((string) ($course['Descripcion'] ?: 'Consulta los detalles del curso para conocer sus contenidos y Requisitos.')) ?></p>
                    <dl class="course-meta">
                        <div><dt>Instructor</dt><dd><?= e($course['instructor_nombres'] . ' ' . $course['instructor_apellidos']) ?></dd></div>
                        <div><dt>Modalidad</dt><dd><?= e($course['Modalidad'] === 'Hibrido' ? 'Híbrido' : (string) $course['Modalidad']) ?></dd></div>
                        <div><dt>Duración</dt><dd><?= e((string) $course['Duracion']) ?></dd></div>
                        <div><dt>Cupos disponibles</dt><dd><?= (int) $course['Cupos_Disponibles'] ?></dd></div>
                    </dl>
                    <div class="course-card-footer">
                        <span class="course-price">$<?= number_format((float) $course['Costo_curso'], 2) ?></span>
                        <a class="button" href="/catalogo/curso?id=<?= (int) $course['IdCurso'] ?>">Ver detalles</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($courses === []): ?>
                <p class="empty-state card catalog-empty">No hay cursos que coincidan con estos filtros.</p>
            <?php endif; ?>
        </section>
