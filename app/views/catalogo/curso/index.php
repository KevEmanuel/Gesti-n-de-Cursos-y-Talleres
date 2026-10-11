<?php

declare(strict_types=1);

/** Detalle publico de un curso y su opci?n de inscripcion. */

?>
<main class="container">
        <header class="page-header">
            <div><a href="/catalogo">← Volver al catálogo</a><h1><?= e($title) ?></h1></div>
            <?php if ($isAdministrator): ?>
                <a class="button" href="/">Volver al panel</a>
            <?php elseif ($isStudent): ?>
                <a class="button" href="/mi-cuenta">Mi cuenta</a>
            <?php elseif ($isInstructor): ?>
                <a class="button" href="/instructor">Mis cursos</a>
            <?php else: ?>
                <a href="/login">Iniciar sesión</a>
            <?php endif; ?>
        </header>

        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>

        <?php if ($course === null): ?>
            <section class="card"><p>El curso solicitado no está publicado o ya no está disponible.</p><a class="button" href="/catalogo">Explorar cursos</a></section>
        <?php else: ?>
            <?php $isFull = $course['estado'] === 'Lleno' || (int) $course['cupos_disponibles'] === 0; ?>
            <section class="course-detail card">
                <div class="course-card-top">
                    <span class="course-category"><?= e((string) $course['categoria']) ?></span>
                    <span class="badge <?= $isFull ? 'badge-muted' : 'badge-available' ?>"><?= $isFull ? 'Cupos agotados' : 'Inscripción abierta' ?></span>
                </div>
                <p class="lead-copy"><?= nl2br(e((string) ($course['descripcion'] ?? 'Descripción pendiente.'))) ?></p>
                <dl class="course-meta course-meta-detail">
                    <div><dt>Instructor</dt><dd><?= e($course['instructor_nombres'] . ' ' . $course['instructor_apellidos']) ?></dd></div>
                    <div><dt>Modalidad</dt><dd><?= e($course['modalidad'] === 'Hibrido' ? 'Híbrido' : (string) $course['modalidad']) ?></dd></div>
                    <div><dt>Duración</dt><dd><?= e((string) $course['duracion']) ?></dd></div>
                    <div><dt>Costo</dt><dd>$<?= number_format((float) $course['costo_curso'], 2) ?></dd></div>
                    <div><dt>Cupos disponibles</dt><dd><?= (int) $course['cupos_disponibles'] ?> de <?= (int) $course['maximo_cupos'] ?></dd></div>
                    <div><dt>Período de inscripción</dt><dd>
                        <?php if ($course['periodo_inicio'] !== null): ?>
                            <?= e((string) $course['periodo_inicio']) ?> – <?= e((string) $course['periodo_cierre']) ?>
                        <?php else: ?>Sin período vigente<?php endif; ?>
                    </dd></div>
                </dl>

                <h2>Requisitos</h2>
                <p><?= nl2br(e((string) ($course['requisitos'] ?: 'No se especificaron requisitos.'))) ?></p>

                <h2>Horarios</h2>
                <?php if ($schedules === []): ?>
                    <p>El horario aún no ha sido publicado.</p>
                <?php else: ?>
                    <ul class="schedule-list">
                        <?php foreach ($schedules as $schedule): ?>
                            <li><?= e((string) $schedule['dia']) ?>, <?= e(substr((string) $schedule['hora_inicio'], 0, 5)) ?>–<?= e(substr((string) $schedule['hora_finalizacion'], 0, 5)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <?php if (!$isAdministrator && !$isInstructor): ?>
            <section class="card enrollment-callout">
                <?php if ($isStudent && $activeEnrollment !== null): ?>
                    <h2>Solicitud ya registrada</h2>
                    <p>
                        <?php if ($activeEnrollment['estado_inscripcion'] === 'Pendiente'): ?>
                            Ya enviaste una solicitud para este curso y está pendiente de revisión.
                        <?php else: ?>
                            Ya tienes una inscripción confirmada para este curso.
                        <?php endif; ?>
                    </p>
                    <a class="button button-secondary" href="/mi-cuenta">Ver mis solicitudes</a>
                <?php elseif ($isStudent && $canEnroll): ?>
                    <h2>Solicitar inscripción</h2>
                    <p>solicitud enviada, el sistema reservará un cupo mientras el administrador revisa su petición.</p>
                    <form action="/mi-cuenta/inscribirse" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="id_curso" value="<?= (int) $course['id_curso'] ?>">
                        <button class="button" type="submit">Solicitar inscripción</button>
                    </form>
                <?php elseif ($isStudent): ?>
                    <h2>Inscripción no disponible</h2>
                    <p>Este curso no tiene cupos o no cuenta con un período de inscripción vigente.</p>
                <?php else: ?>
                    <h2>¿Quieres inscribirte?</h2>
                    <p>Inicia sesión o solicita una cuenta de estudiante. El administrador debe aprobarla antes de que puedas solicitar un cupo.</p>
                    <a class="button" href="/login">Iniciar sesión</a>
                    <a class="button button-secondary" href="/estudiante/registro">Solicitar cuenta</a>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
