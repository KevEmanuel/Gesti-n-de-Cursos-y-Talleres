
<nav class="public-nav" aria-label="Navegación principal">
    <a class="public-brand" href="/">
        <span class="public-brand-mark" aria-hidden="true">FC</span>
        <span>Formación Continua</span>
    </a>
    <div class="public-nav-links">
        <a href="/catalogo" aria-current="page">Cursos</a>
    </div>
    <div class="public-nav-actions">
        <?php if ($isAdministrator): ?>
            <a class="button button-secondary" href="/">Volver al panel</a>
        <?php elseif ($isStudent): ?>
            <a class="button button-secondary" href="/mi-cuenta">Mis inscripciones</a>
        <?php elseif ($isInstructor): ?>
            <a class="button button-secondary" href="/instructor">Mis cursos</a>
        <?php else: ?>
            <a href="/login">Iniciar sesión</a>
            <a class="button" href="/estudiante/registro">Crear cuenta</a>
        <?php endif; ?>
    </div>
</nav>
