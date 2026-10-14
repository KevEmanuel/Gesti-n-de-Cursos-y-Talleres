<?php

declare(strict_types=1);

/** Listado publico de cursos con filtros de busqueda. */

?>

<!-- cambio de <main class="container catalog-page"> -->
<main class="container catalog-page">
    <main class="container catalog-page">
        <header class="page-header">
            <div><a href="/">Inicio</a><h1><?= e($title) ?></h1><p>Busca por curso, categoría, modalidad o instructor.</p></div>
            <div class="session-actions">
                <?php if ($isAdministrator): ?>
                    <a class="button" href="/">Volver al panel</a>
                <?php elseif ($isStudent): ?>
                    <a class="button" href="/mi-cuenta">Mi cuenta</a>
                <?php elseif ($isInstructor): ?>
                    <a class="button" href="/instructor">Mis cursos</a>
                <?php else: ?>
                    <a href="/login">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>

        <form action="/catalogo" method="get" class="catalog-filter card">
            <label>Buscar<input type="search" name="busqueda" value="<?= e($filters['busqueda']) ?>" placeholder="Nombre, categoría o instructor"></label>
            <label>Categoría
                <select name="id_categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id_categoria'] ?>" <?= $filters['id_categoria'] === (string) $category['id_categoria'] ? 'selected' : '' ?>><?= e((string) $category['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Modalidad
                <select name="modalidad">
                    <option value="">Todas</option>
                    <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                        <option value="<?= $modality ?>" <?= $filters['modalidad'] === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
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
                        $isFull = $course['estado'] === 'Lleno' || (int) $course['cupos_disponibles'] === 0;
                        $hasPeriod = $course['periodo_inicio'] !== null;
                        ?>
                        <span class="badge <?= $isFull || !$hasPeriod ? 'badge-muted' : 'badge-available' ?>">
                            <?= $isFull ? 'Cupos agotados' : ($hasPeriod ? 'Inscripción abierta' : 'Sin período vigente') ?>
                        </span>
                    </div>
                    <h2><?= e((string) $course['nombre_curso']) ?></h2>
                    <p><?= e((string) ($course['descripcion'] ?: 'Consulta los detalles del curso para conocer sus contenidos y requisitos.')) ?></p>
                    <dl class="course-meta">
                        <div><dt>Instructor</dt><dd><?= e($course['instructor_nombres'] . ' ' . $course['instructor_apellidos']) ?></dd></div>
                        <div><dt>Modalidad</dt><dd><?= e($course['modalidad'] === 'Hibrido' ? 'Híbrido' : (string) $course['modalidad']) ?></dd></div>
                        <div><dt>Duración</dt><dd><?= e((string) $course['duracion']) ?></dd></div>
                        <div><dt>Cupos disponibles</dt><dd><?= (int) $course['cupos_disponibles'] ?></dd></div>
                    </dl>
                    <div class="course-card-footer">
                        <span class="course-price">$<?= number_format((float) $course['costo_curso'], 2) ?></span>
                        <a class="button" href="/catalogo/curso?id=<?= (int) $course['id_curso'] ?>">Ver detalles</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($courses === []): ?>
                <p class="empty-state card catalog-empty">No hay cursos que coincidan con estos filtros.</p>
            <?php endif; ?>
        </section>
    </main>
