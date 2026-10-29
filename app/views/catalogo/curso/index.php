<?php

declare(strict_types=1);

/** Detalle publico de un curso y su opci?n de inscripcion. */

?>
<main class="container course-detail-page">
        <?php require __DIR__ . '/../../shared/public-navigation.php'; ?>
        <nav class="course-breadcrumb" aria-label="Ruta de navegación"><a href="/catalogo">Cursos</a><span aria-hidden="true">/</span><span><?= e($title) ?></span></nav>

        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>

        <?php if ($course === null): ?>
            <section class="card">
                <h1 class="course-detail-title"><?= e($title) ?></h1>
                <p>El curso solicitado no está publicado o ya no está disponible.</p>
                <a class="button" href="/catalogo">Explorar cursos</a>
            </section>
        <?php else: ?>
            <?php $isFull = $course['Estado'] === 'Lleno' || (int) $course['Cupos_Disponibles'] === 0; ?>
            <section class="course-detail card">
                <div class="course-detail-main">
                    <div class="course-card-top">
                        <span class="course-category"><?= e((string) $course['categoria']) ?></span>
                        <span class="badge <?= $isFull ? 'badge-muted' : 'badge-available' ?>"><?= $isFull ? 'Cupos agotados' : 'Inscripción abierta' ?></span>
                    </div>
                    <h1 class="course-detail-title"><?= e($title) ?></h1>
                    <p class="lead-copy"><?= nl2br(e((string) ($course['Descripcion'] ?? 'Descripción pendiente.'))) ?></p>

                    <section class="course-detail-section">
                        <h2>Requisitos</h2>
                        <p><?= nl2br(e((string) ($course['Requisitos'] ?: 'No se especificaron Requisitos.'))) ?></p>
                    </section>

                    <section class="course-detail-section">
                        <h2>Horarios</h2>
                        <?php if ($schedules === []): ?>
                            <p>El horario aún no ha sido publicado.</p>
                        <?php else: ?>
                            <ul class="schedule-list">
                                <?php foreach ($schedules as $schedule): ?>
                                    <li><?= e((string) $schedule['Dia']) ?>, <?= e(substr((string) $schedule['HoraInicio'], 0, 5)) ?>–<?= e(substr((string) $schedule['Hora_finalizacion'], 0, 5)) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="course-detail-aside">
                    <div class="course-instructor-card">
                        <p class="eyebrow">INSTRUCTOR</p>
                        <h2><?= e($course['instructor_nombres'] . ' ' . $course['instructor_apellidos']) ?></h2>
                    </div>
                    <dl class="course-meta course-meta-detail">
                        <div><dt>Costo</dt><dd>$<?= number_format((float) $course['Costo_curso'], 2) ?></dd></div>
                        <div><dt>Cupos disponibles</dt><dd><?= (int) $course['Cupos_Disponibles'] ?> de <?= (int) $course['Maximo_cupos'] ?></dd></div>
                        <div><dt>Duración</dt><dd><?= e((string) $course['Duracion']) ?></dd></div>
                        <div><dt>Modalidad</dt><dd><?= e($course['Modalidad'] === 'Hibrido' ? 'Híbrido' : (string) $course['Modalidad']) ?></dd></div>
                        <div><dt>Período de inscripción</dt><dd>
                            <?php if ($course['periodo_inicio'] !== null): ?>
                                <?= e((string) $course['periodo_inicio']) ?> – <?= e((string) $course['periodo_cierre']) ?>
                            <?php else: ?>Sin período vigente<?php endif; ?>
                        </dd></div>
                    </dl>
                    <?php if (!$isAdministrator && !$isInstructor): ?>
                        <section class="card enrollment-callout">
                            <?php if ($isStudent && $activeEnrollment !== null): ?>
                                <h2>Solicitud ya registrada</h2>
                                <p>
                                    <?php if ($activeEnrollment['EstadoInscripcion'] === 'Pendiente'): ?>
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
                                    <input type="hidden" name="IdCurso" value="<?= (int) $course['IdCurso'] ?>">
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
                </aside>
            </section>
        <?php endif; ?>
    </main>
