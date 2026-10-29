<?php

declare(strict_types=1);

/** Formulario para administrar las categorias. */

?>
<main class="container">
        <header class="page-header">
            <div>
                <a href="/">Volver al dashboard</a>
                <h1><?= e($title) ?></h1>
            </div>
        </header>

        <?php if ($message !== null): ?>
            <p class="notice notice-success" role="status"><?= e($message) ?></p>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <section class="card">
            <h2>Nueva categoría</h2>
            <form action="/categorias/crear" method="post" class="category-create-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>
                    Nombre
                    <input name="Nombre" maxlength="150" required placeholder="Ejemplo: Fotografía" value="<?= e($old['Nombre'] ?? '') ?>">
                </label>
                <label>
                    Descripción
                    <input name="Descripcion" maxlength="500" placeholder="Describe brevemente la categoría" value="<?= e($old['Descripcion'] ?? '') ?>">
                </label>
                <button type="submit" class="button">Crear categoría</button>
            </form>
        </section>

        <section class="card category-list">
            <div class="toolbar">
                <h2>Categorías registradas</h2>
                <label>
                    Buscar
                    <input type="search" placeholder="Buscar categorías" data-table-search>
                </label>
            </div>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td colspan="3">
                                    <form action="/categorias/actualizar" method="post" class="category-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="IdCategoria" value="<?= (int) $category['IdCategoria'] ?>">
                                        <label>
                                            <span class="visually-hidden">Nombre</span>
                                            <input name="Nombre" maxlength="150" required value="<?= e((string) $category['Nombre']) ?>">
                                        </label>
                                        <label>
                                            <span class="visually-hidden">Descripción</span>
                                            <input name="Descripcion" maxlength="500" value="<?= e((string) ($category['Descripcion'] ?? '')) ?>">
                                        </label>
                                        <label>
                                            <span class="visually-hidden">Estado</span>
                                            <select name="Estado">
                                                <option value="Activo" <?= $category['Estado'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                                                <option value="Inactivo" <?= $category['Estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                            </select>
                                        </label>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td>
                                    <form action="/categorias/estado" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="IdCategoria" value="<?= (int) $category['IdCategoria'] ?>">
                                        <?php $nextStatus = $category['Estado'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>
                                        <input type="hidden" name="Estado" value="<?= $nextStatus ?>">
                                        <button type="submit" class="button button-secondary">
                                            <?= $nextStatus === 'Activo' ? 'Activar' : 'Desactivar' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty-row <?= $categories === [] ? '' : 'hidden' ?>>
                            <td colspan="4" class="empty-state">No hay categorías registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
