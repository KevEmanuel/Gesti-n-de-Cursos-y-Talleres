<?php

declare(strict_types=1);

/**
 * formulario para registrar cursos 
 * Adminitracion de cursos, solo para la vista de Administrador 
 * 
*/

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
                    <label>Nombre del curso<input name="Nombre_Curso" maxlength="200" required placeholder="Ejemplo:  Introducción a la fotografía" value="<?= e($old['Nombre_Curso'] ?? '') ?>"></label>
                    <label>Categoría
                        <select name="IdCategoria" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['IdCategoria'] ?>" <?= (string) ($old['IdCategoria'] ?? '') === (string) $category['IdCategoria'] ? 'selected' : '' ?>><?= e((string) $category['Nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Instructor
                        <select name="IdInstructor" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?= (int) $instructor['IdInstructor'] ?>" <?= (string) ($old['IdInstructor'] ?? '') === (string) $instructor['IdInstructor'] ? 'selected' : '' ?>><?= e($instructor['Apellidos'] . ', ' . $instructor['Nombres']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Duración<input name="Duracion" maxlength="100" required placeholder="Ejemplo: 40 horas" value="<?= e($old['Duracion'] ?? '') ?>"></label>
                    <label>Modalidad
                        <select name="Modalidad" required>
                            <option value="">Selecciona</option>
                            <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                                <option value="<?= $modality ?>" <?= ($old['Modalidad'] ?? '') === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Costo<input type="text" name="Costo_curso" inputmode="decimal" maxlength="11" pattern="[0-9]{1,8}(\.[0-9]{1,2})?" required title="usa punto para los centavos y máximo 2 decimales." placeholder="Ejemplo: $25.00" value="<?= e($old['Costo_curso'] ?? '0.00') ?>"></label>
                    <label>Cupos máximos<input type="number" name="Maximo_cupos" min="1" max="4294967295" step="1" required placeholder="Ej. 20" value="<?= e((string) ($old['Maximo_cupos'] ?? '')) ?>"></label>
                    <label class="course-wide">Descripción del Curso<textarea name="Descripcion" rows="3" placeholder="Describe brevemente el curso"><?= e($old['Descripcion'] ?? '') ?></textarea></label>
                    <label class="course-wide">Requisitos del Curso<textarea name="Requisitos" rows="3" placeholder="Indica conocimientos o materiales necesarios"><?= e($old['Requisitos'] ?? '') ?></textarea></label>
                    <button type="submit" class="button">Crear curso</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="card profile-list">
            <div class="toolbar"><h2>Cursos registrados</h2><label>Buscar<input type="search" placeholder="Buscar cursos" data-table-search></label></div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead><tr><th>Detalle de Cursos</th></tr></thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr class="course-record-row">
                                <td>
                                    <form action="/cursos/actualizar" method="post" class="course-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="IdCurso" value="<?= (int) $course['IdCurso'] ?>">
                                        <section class="course-edit-section">
                                            <h3>Datos principales</h3>
                                            <div class="course-edit-main-fields">
                                                <label>Nombre del curso<input name="Nombre_Curso" maxlength="200" required value="<?= e((string) $course['Nombre_Curso']) ?>"></label>
                                                <label>Categoría<select name="IdCategoria" required>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= (int) $category['IdCategoria'] ?>" <?= (int) $course['IdCategoria'] === (int) $category['IdCategoria'] ? 'selected' : '' ?>><?= e((string) $category['Nombre']) ?></option>
                                                    <?php endforeach; ?>
                                                </select></label>
                                                <label>Instructor<select name="IdInstructor" required>
                                                    <?php foreach ($instructors as $instructor): ?>
                                                        <option value="<?= (int) $instructor['IdInstructor'] ?>" <?= (int) $course['IdInstructor'] === (int) $instructor['IdInstructor'] ? 'selected' : '' ?>><?= e($instructor['Apellidos'] . ', ' . $instructor['Nombres']) ?></option>
                                                    <?php endforeach; ?>
                                                </select></label>
                                                <label>Duración<input name="Duracion" maxlength="100" required title="Escribe una cantidad positiva de horas, por ejemplo: 40 horas." value="<?= e((string) $course['Duracion']) ?>"></label>
                                                <label>Modalidad<select name="Modalidad">
                                                    <?php foreach (['Presencial', 'Virtual', 'Hibrido'] as $modality): ?>
                                                        <option value="<?= $modality ?>" <?= $course['Modalidad'] === $modality ? 'selected' : '' ?>><?= $modality === 'Hibrido' ? 'Híbrido' : $modality ?></option>
                                                    <?php endforeach; ?>
                                                </select></label>
                                            </div>
                                        </section>
                                        <section class="course-edit-section">
                                            <h3>Descripción y requisitos</h3>
                                            <div class="course-edit-details-fields">
                                                <label class="course-edit-description">Descripción<textarea name="Descripcion" rows="3" title="Define descripcion para el curso"><?= e((string) ($course['Descripcion'] ?? '')) ?></textarea></label>
                                                <label class="course-edit-requirements">Requisitos<textarea name="Requisitos" rows="3" title="Define Requisitos el curso"><?= e((string) ($course['Requisitos'] ?? '')) ?></textarea></label>
                                                <label>Costo<input type="text" name="Costo_curso" inputmode="decimal" maxlength="11" pattern="[0-9]{1,8}(\.[0-9]{1,2})?" required title="usa punto para los centavos y máximo 2 decimales." value="<?= e((string) $course['Costo_curso']) ?>"></label>
                                                <label>Cupos máximos<input type="number" name="Maximo_cupos" min="<?= max(1, (int) $course['InscritosActivos']) ?>" max="4294967295" step="1" required title="Ingresa un número entero no menor que los inscritos actuales." value="<?= (int) $course['Maximo_cupos'] ?>"></label>
                                            </div>
                                        </section>
                                        <div class="course-edit-footer">
                                            <h3>Cupos y estado</h3>
                                            <div class="course-capacity-info" aria-label="Disponibilidad de cupos">
                                                <span>Inscritos<strong><?= (int) $course['InscritosActivos'] ?></strong></span>
                                                <span>Libres<strong><?= (int) $course['Cupos_Disponibles'] ?></strong></span>
                                            </div>
                                            <label>Estado<select name="Estado">
                                                <?php foreach (['Activo', 'Cerrado', 'Lleno', 'Cancelado'] as $status): ?>
                                                    <option value="<?= $status ?>" <?= $course['Estado'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                                <?php endforeach; ?>
                                            </select></label>
                                            <button type="submit" class="button">Guardar cambios</button>
                                        </div>
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
