<?php

declare(strict_types=1);

/** Formulario y listado para administrar categorias. */

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
                    <input name="nombre" maxlength="150" required placeholder="Ej. Fotografía" value="<?= e($old['nombre'] ?? '') ?>">
                    <small>El nombre debe incluir al menos una letra.</small>
                </label>
                <label>
                    Descripción
                    <input name="descripcion" maxlength="500" placeholder="Describe brevemente la categoría" value="<?= e($old['descripcion'] ?? '') ?>">
                    <small>Opcional; si la completas, incluye al menos una letra.</small>
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
                                        <input type="hidden" name="id_categoria" value="<?= (int) $category['id_categoria'] ?>">
                                        <label>
                                            <span class="visually-hidden">Nombre</span>
                                            <input name="nombre" maxlength="150" required value="<?= e((string) $category['nombre']) ?>">
                                        </label>
                                        <label>
                                            <span class="visually-hidden">Descripción</span>
                                            <input name="descripcion" maxlength="500" value="<?= e((string) ($category['descripcion'] ?? '')) ?>">
                                        </label>
                                        <label>
                                            <span class="visually-hidden">Estado</span>
                                            <select name="estado">
                                                <option value="Activo" <?= $category['estado'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                                                <option value="Inactivo" <?= $category['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                            </select>
                                        </label>
                                        <button type="submit" class="button">Guardar</button>
                                    </form>
                                </td>
                                <td>
                                    <form action="/categorias/estado" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_categoria" value="<?= (int) $category['id_categoria'] ?>">
                                        <?php $nextStatus = $category['estado'] === 'Activo' ? 'Inactivo' : 'Activo'; ?>
                                        <input type="hidden" name="estado" value="<?= $nextStatus ?>">
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
