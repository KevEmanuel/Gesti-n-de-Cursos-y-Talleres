<?php

declare(strict_types=1);

/** Pagina de inicio y navegacion segun el rol del usuario. */

$isAdministrator = is_array($user) && ($user['role'] ?? null) === 'administrator';
$isStudent = is_array($user) && ($user['role'] ?? null) === 'student';
$isInstructor = is_array($user) && ($user['role'] ?? null) === 'instructor';
?>
    <main class="container home-page">
        <header class="page-header">
            <div>
                <?php if ($isAdministrator): ?>
                    <p class="eyebrow">Panel de Administrativo</p>
                    <h1>Panel de administración</h1>
                    <p>Gestiona los cursos, las cuentas y las solicitudes de inscripción.</p>
                <?php else: ?>
                    <p class="eyebrow">Formación y aprendizaje</p>
                    <h1>Sistema de cursos y Talleres</h1>
                    <p>Explora cursos y talleres para desarrollar nuevas habilidades.</p>
                <?php endif; ?>
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

        <?php if ($isAdministrator): ?>
            <section class="hero-card admin-dashboard-welcome">
                <div>
                    <p class="eyebrow">Formación Continua</p>
                    <h2>Gestión de la plataforma</h2>
                    <p>Administra el catálogo, los usuarios y las inscripciones desde los módulos disponibles.</p>
                </div>
                <a class="button" href="/solicitudes">Revisar solicitudes</a>
            </section>
        <?php else: ?>
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Catálogo público</p>
                    <h2>Encuentra tu próximo curso</h2>
                    <p>Consulta contenidos, Modalidad, instructor, horarios y disponibilidad.</p>
                </div>
                <a class="button" href="/catalogo">Ver cursos</a>
            </section>
        <?php endif; ?>

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
    <?php if (!$isAdministrator): ?>
        <footer class="home-footer">
            <div class="home-footer-content">
                <div class="home-footer-brand">
                    <a class="public-brand" href="/">
                        <span class="public-brand-mark" aria-hidden="true">FC</span>
                        <span>Formación Continua</span>
                    </a>
                    <p>Curso y talleres para seguir aprendiendo.</p>
                </div>
                <div class="home-footer-contact">
                    <h2>Contacto de demostración</h2>
                    <a href="mailto:contacto@formacioncontinua.ues.edu.sv">contacto@formacioncontinua.ues.edu.sv</a>
                    <a href="tel:+50300000000">+503 0000-0000</a>
                </div>
                <div class="home-footer-social">
                    <h2>Redes sociales</h2>
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer">Facebook</a>
                </div>
            </div>
            <div class="home-footer-bottom">
                <p>&copy; <?= date('Y') ?> Formación Continua curso y talleres. Todos los derechos reservados.</p>
            </div>
        </footer>
    <?php endif; ?>
