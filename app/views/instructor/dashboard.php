<?php

declare(strict_types=1);

/** Cursos, horarios y estudiantes del instructor autenticado. */

$weekdays = [
    'Lunes' => 'Lunes',
    'Martes' => 'Martes',
    'Miercoles' => 'Miércoles',
    'Jueves' => 'Jueves',
    'Viernes' => 'Viernes',
    'Sabado' => 'Sábado',
    'Domingo' => 'Domingo',
];
?>
<main class="container">
        <header class="page-header">
            <div>
                <p class="eyebrow">Portal docente</p>
                <h1>Mis cursos</h1>
                <p>Hola, <?= e((string) $user['name']) ?>. Consulta tus cursos, horarios y estudiantes inscritos.</p>
            </div>
            <div class="session-actions">
                <a class="button button-secondary" href="/">Inicio</a>
                <form action="/logout" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <button class="button button-secondary" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <?php if ($courses === []): ?>
            <section class="card empty-state">
                Todavía no tienes cursos asignados. Contacta a administración para más información.
            </section>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <section class="card">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow"><?= e((string) $course['categoria']) ?></p>
                            <h2><?= e((string) $course['nombre_curso']) ?></h2>
                        </div>
                        <span class="badge badge-muted"><?= e((string) $course['estado']) ?></span>
                    </div>
                    <?php if ($course['descripcion'] !== null && $course['descripcion'] !== ''): ?>
                        <p><?= e((string) $course['descripcion']) ?></p>
                    <?php endif; ?>
                    <p><strong>Duración:</strong> <?= e((string) $course['duracion']) ?>
                        <span aria-hidden="true"> · </span><strong>Modalidad:</strong> <?= e((string) $course['modalidad']) ?></p>
                    <?php if ($course['requisitos'] !== null && $course['requisitos'] !== ''): ?>
                        <p><strong>Requisitos:</strong> <?= e((string) $course['requisitos']) ?></p>
                    <?php endif; ?>

                    <h3>Horarios</h3>
                    <?php if ($course['horarios'] === []): ?>
                        <p class="muted-copy">No hay horarios registrados.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($course['horarios'] as $schedule): ?>
                                <li><?= e($weekdays[$schedule['dia']] ?? (string) $schedule['dia']) ?>:
                                    <?= e(substr((string) $schedule['hora_inicio'], 0, 5)) ?>–<?= e(substr((string) $schedule['hora_finalizacion'], 0, 5)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <h3>Estudiantes inscritos</h3>
                    <?php if ($course['estudiantes'] === []): ?>
                        <p class="muted-copy">Todavía no hay estudiantes con inscripción confirmada.</p>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead><tr><th>Carnet</th><th>Nombre</th><th>Fecha de inscripción</th></tr></thead>
                                <tbody>
                                    <?php foreach ($course['estudiantes'] as $student): ?>
                                        <tr>
                                            <td><?= e((string) $student['carnet']) ?></td>
                                            <td><?= e(trim((string) $student['nombres'] . ' ' . (string) $student['apellidos'])) ?></td>
                                            <td><?= e((string) $student['fecha_inscripcion']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
