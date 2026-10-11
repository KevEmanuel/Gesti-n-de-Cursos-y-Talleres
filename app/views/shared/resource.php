<?php

declare(strict_types=1);

/** Vista compartida para la presentacion de recursos tabulares. */
?>
<main class="container">
        <header class="page-header">
            <div>
                <a href="/">Volver al dashboard</a>
                <h1><?= e($title) ?></h1>
            </div>
            <button type="button" class="button" data-open-form>Nuevo registro</button>
        </header>
        <section class="card">
            <div class="toolbar">
                <label>
                    Buscar
                    <input type="search" placeholder="Buscar en <?= e($title) ?>" data-table-search>
                </label>
                <label>
                    Estado
                    <select data-status-filter>
                        <option value="">Todos</option>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </label>
            </div>
            <form class="module-form is-hidden" data-module-form method="post">
                <h2>Nuevo registro</h2>
                <div class="form-grid">
                    <?php foreach ($columns as $column): ?>
                        <?php if ($column === 'ID'): continue; endif; ?>
                        <label>
                            <?= e($column) ?>
                            <input name="<?= e(strtolower(str_replace(' ', '_', $column))) ?>" type="text">
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button">Guardar</button>
                    <button type="button" class="button button-secondary" data-close-form>Cancelar</button>
                </div>
            </form>
            <div class="table-wrapper">
                <table data-module-table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th><?= e($column) ?></th>
                            <?php endforeach; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-empty-row>
                            <td colspan="<?= count($columns) + 1 ?>" class="empty-state">
                                No hay registros todavía. El modelo y el CRUD se conectarán en la siguiente etapa.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
