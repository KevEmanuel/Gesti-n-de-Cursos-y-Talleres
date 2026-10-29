<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px 30px; }
        body { color: #1c2b2e; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { color: #14595f; font-size: 19px; margin: 0 0 6px; }
        .subtitle { color: #5c6b6d; font-size: 10px; margin: 0 0 14px; }
        .filters { border: 1px solid #d9e2df; border-radius: 5px; margin-bottom: 14px; padding: 9px 11px; }
        .filters span { display: inline-block; margin-right: 22px; }
        .filters strong { color: #14595f; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #14595f; color: #fff; font-size: 8px; padding: 8px 7px; text-align: left; }
        td { border-bottom: 1px solid #d9e2df; padding: 7px; }
        tr:nth-child(even) td { background: #f4f8f6; }
        .number { text-align: right; }
        .empty { color: #5c6b6d; padding: 16px 7px; text-align: center; }
        .footer { color: #5c6b6d; font-size: 8px; margin-top: 12px; text-align: right; }
    </style>
</head>
<body>
    <h1>Reporte de cursos e inscripciones</h1>
    <p class="subtitle">Generado el <?= e($generatedAt) ?></p>
    <div class="filters">
        <span><strong>Desde:</strong> <?= e($fechaInicio) ?></span>
        <span><strong>Hasta:</strong> <?= e($fechaCierre) ?></span>
        <span><strong>Categoría:</strong> <?= e($categoryName) ?></span>
        <span><strong>Modalidad:</strong> <?= e($modality) ?></span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Curso</th>
                <th>Categoría</th>
                <th>Modalidad</th>
                <th>Estado</th>
                <th class="number">Inscritos activos</th>
                <th class="number">Capacidad</th>
                <th class="number">Cupos disponibles</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $courseStatus = (int) $row['Cupos_Disponibles'] === 0 ? 'Lleno' : (string) $row['Estado']; ?>
                <tr>
                    <td><?= e((string) $row['Nombre_Curso']) ?></td>
                    <td><?= e((string) $row['categoria']) ?></td>
                    <td><?= e((string) $row['Modalidad'] === 'Hibrido' ? 'Híbrido' : $row['Modalidad']) ?></td>
                    <td><?= e($courseStatus) ?></td>
                    <td class="number"><?= (int) $row['InscritosActivos'] ?></td>
                    <td class="number"><?= (int) $row['Maximo_cupos'] ?></td>
                    <td class="number"><?= (int) $row['Cupos_Disponibles'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td class="empty" colspan="7">No hay resultados para los filtros seleccionados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <p class="footer"><?= count($rows) ?> curso(s)</p>
</body>
</html>
