<?php

declare(strict_types=1);

/** Formulario y listado para administrar cursos. */

?>
<main class="container">
        <header class="page-header">
            <div><a href="/">Volver al dashboard</a><h1><?= e($title) ?></h1></div>
        </header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>

        <section class="card">
            <h2>Registrar curso</h2>
            <?php if ($categories === [] || $instructors === []): ?>
                <p>Para registrar un curso, primero debe existir al menos una categoría activa y un instructor activo.</p>
            <?php else: ?>
                <form action="/cursos/crear" method="post" class="course-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <label>Nombre<input name="nombre_curso" maxlength="200" required value="<?= e($old['nombre_curso'] ?? '') ?>"></label>
                    <label>Categoría
                        <select name="id_categoria" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id_categoria'] ?>" <?= (string) ($old['id_categoria'] ?? '') === (string) $category['id_categoria'] ? 'selected' : '' ?>><?= e((string) $category['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Instructor
                        <select name="id_instructor" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?= (int) $instructor['id_instructor'] ?>" <?= (string) ($old['id_instructor'] ?? '') === (string) $instructor['id_instructor'] ? 'selected' : '' ?>><?= e($instructor['apellidos'] . ', ' . $instructor['nombres']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Duración<input name="duracion" maxlength="100" required placeholder="Ej. 40 horas" value="<?= e($old['duracion'] ?? '') ?>"></label>
                    <label>Modalidad
                        <select name="modalidad" required>
                            <option value="">Selecciona</option>
                            <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                                <option value="<?= $modality ?>" <?= ($old['modalidad'] ?? '') === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Costo<input type="number" name="costo_curso" min="0" step="0.01" required value="<?= e($old['costo_curso'] ?? '0.00') ?>"></label>
                    <label>Cupos máximos<input type="number" name="maximo_cupos" min="1" required value="<?= e((string) ($old['maximo_cupos'] ?? '')) ?>"></label>
                    <label class="course-wide">Descripción<textarea name="descripcion" rows="3"><?= e($old['descripcion'] ?? '') ?></textarea></label>
                    <label class="course-wide">Requisitos<textarea name="requisitos" rows="3"><?= e($old['requisitos'] ?? '') ?></textarea></label>
                    <button type="submit" class="button">Crear curso</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="card profile-list">
            <div class="toolbar"><h2>Cursos registrados</h2><label>Buscar<input type="search" placeholder="Buscar cursos" data-table-search></label></div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Curso y datos editables</th></tr></thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td>
                                    <form action="/cursos/actualizar" method="post" class="course-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_curso" value="<?= (int) $course['id_curso'] ?>">
                                        <label><span class="visually-hidden">Curso</span><input name="nombre_curso" maxlength="200" required value="<?= e((string) $course['nombre_curso']) ?>"></label>
                                        <label><span class="visually-hidden">Categoría</span><select name="id_categoria" required>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= (int) $category['id_categoria'] ?>" <?= (int) $course['id_categoria'] === (int) $category['id_categoria'] ? 'selected' : '' ?>><?= e((string) $category['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select></label>
                                        <label><span class="visually-hidden">Instructor</span><select name="id_instructor" required>
                                            <?php foreach ($instructors as $instructor): ?>
                                                <option value="<?= (int) $instructor['id_instructor'] ?>" <?= (int) $course['id_instructor'] === (int) $instructor['id_instructor'] ? 'selected' : '' ?>><?= e($instructor['apellidos'] . ', ' . $instructor['nombres']) ?></option>
                                            <?php endforeach; ?>
                                        </select></label>
                                        <label><span class="visually-hidden">Duración</span><input name="duracion" maxlength="100" required value="<?= e((string) $course['duracion']) ?>"></label>
                                        <label><span class="visually-hidden">Modalidad</span><select name="modalidad">
                                            <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                                                <option value="<?= $modality ?>" <?= $course['modalidad'] === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
                                            <?php endforeach; ?>
                                        </select></label>
                                        <label><span class="visually-hidden">Descripción</span><input name="descripcion" value="<?= e((string) ($course['descripcion'] ?? '')) ?>"></label>
                                        <label><span class="visually-hidden">Requisitos</span><input name="requisitos" value="<?= e((string) ($course['requisitos'] ?? '')) ?>"></label>
                                        <label><span class="visually-hidden">Costo</span><input type="number" name="costo_curso" min="0" step="0.01" required value="<?= e((string) $course['costo_curso']) ?>"></label>
                                        <label><span class="visually-hidden">Cupos máximos</span><input type="number" name="maximo_cupos" min="<?= (int) $course['inscritos_activos'] ?>" required value="<?= (int) $course['maximo_cupos'] ?>"></label>
                                        <span><?= e((string) $course['categoria']) ?> · <?= e($course['apellidos'] . ', ' . $course['nombres']) ?></span>
                                        <span>Inscritos: <?= (int) $course['inscritos_activos'] ?> · Libres: <?= (int) $course['cupos_disponibles'] ?></span>
                                        <label><span class="visually-hidden">Estado</span><select name="estado">
                                            <?php foreach (['Activo', 'Cerrado', 'Lleno', 'Cancelado'] as $status): ?>
                                                <option value="<?= $status ?>" <?= $course['estado'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                            <?php endforeach; ?>
                                        </select></label>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $courses === [] ? '' : 'hidden' ?>><td class="empty-state">No hay cursos registrados.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
