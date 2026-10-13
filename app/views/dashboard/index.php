<?php

declare(strict_types=1);

/** P?gina de inicio y navegaci?n segun el rol del usuario. */

$isAdministrator = is_array($user) && ($user['role'] ?? null) === 'administrator';
$isStudent = is_array($user) && ($user['role'] ?? null) === 'student';
$isInstructor = is_array($user) && ($user['role'] ?? null) === 'instructor';
?>
<!-- <main class="container"> cambio efectuado -->
    <main class="container home-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">Formación y aprendizaje</p>
                <h1>Sistema de cursos y Talleres</h1>
                <p>Explora cursos y talleres para desarrollar nuevas habilidades.</p>
            </div>
            <div class="session-actions">
                <?php if (is_array($user)): ?>
                    <span>Sesión: <?= e((string) $user['name']) ?></span>
                    <?php if ($isStudent): ?><a class="button" href="/mi-cuenta">Mi cuenta</a><?php endif; ?>
                    <?php if ($isInstructor): ?><a class="button" href="/instructor">Mis cursos</a><?php endif; ?>
                    <form action="/logout" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <button class="button button-secondary" type="submit">Cerrar sesión</button>
                    </form>
                <?php else: ?>
                    <a class="button" href="/login">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </header>

        <section class="hero-card">
            <div>
                <p class="eyebrow">Catálogo público</p>
                <h2>Encuentra tu próximo curso</h2>
                <p>Consulta contenidos, modalidad, instructor, horarios y disponibilidad.</p>
            </div>
            <a class="button" href="/catalogo">Ver cursos</a>
        </section>

        <?php if ($isAdministrator): ?>
            <section class="module-section">
                <h2>Administración</h2>
                <nav aria-label="Módulos administrativos">
                    <a href="/categorias">Categorías</a>
                    <a href="/cursos">Cursos</a>
                    <a href="/estudiantes">Estudiantes</a>
                    <a href="/instructores">Instructores</a>
                    <a href="/horarios">Horarios</a>
                    <a href="/periodos">Períodos</a>
                    <a href="/reportes">Reportes</a>
                    <a href="/solicitudes">Solicitudes</a>
                </nav>
            </section>
        <?php elseif (!$isStudent && !$isInstructor): ?>
            <p class="muted-copy">¿Ya tienes una cuenta? <a href="/login">Inicia sesión</a>. Si aún no tienes una cuenta de estudiante, <a href="/estudiante/registro">presenta una solicitud</a> para que el administrador la revise.</p>
        <?php endif; ?>
    </main>
