<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Curso Taller') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAPJcXn/tWtIaxVXM" crossorigin="anonymous" defer></script>
    <script src="/js/app.js" defer></script>
</head>
<body>
<?php
$isAdministratorLayout = is_array($_SESSION['user'] ?? null)
    && ($_SESSION['user']['role'] ?? null) === 'administrator';
$currentAdminPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$currentAdminPath = is_string($currentAdminPath) ? $currentAdminPath : '/';
$adminName = trim((string) ($_SESSION['user']['name'] ?? ''));
?>
<?php if ($isAdministratorLayout): ?>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="/">
                <span class="admin-brand-mark" aria-hidden="true">FC</span>
                <span>Formación Continua</span>
            </a>
            <p class="admin-nav-label">MENÚ PRINCIPAL</p>
            <nav class="admin-nav" aria-label="Navegación administrativa">
                <?php foreach ([
                    '/' => 'Dashboard',
                    '/cursos' => 'Cursos',
                    '/categorias' => 'Categorías',
                    '/instructores' => 'Instructores',
                    '/horarios' => 'Horarios',
                    '/periodos' => 'Períodos',
                    '/estudiantes' => 'Estudiantes',
                    '/solicitudes' => 'Solicitudes',
                    '/reportes' => 'Reportes',
                ] as $adminPath => $adminLabel): ?>
                    <a href="<?= e($adminPath) ?>" <?= $currentAdminPath === $adminPath ? 'aria-current="page"' : '' ?>>
                        <span class="admin-nav-mark" aria-hidden="true"><?= e(mb_substr($adminLabel, 0, 1, 'UTF-8')) ?></span>
                        <span><?= e($adminLabel) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="admin-sidebar-footer">
                <span class="admin-avatar" aria-hidden="true">A</span>
                <span><strong><?= e($adminName !== '' ? $adminName : 'Administración') ?></strong><small>Administrador</small></span>
            </div>
        </aside>
        <div class="admin-stage">
            <header class="admin-topbar">
                <span>Panel de administración</span>
                <a href="/catalogo">Ver catálogo público</a>
            </header>
            <?= $content ?>
        </div>
    </div>
<?php else: ?>
<?= $content ?>
<?php endif; ?>
</body>
</html>
