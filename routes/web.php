<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$route = trim($path, '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Mapea cada ruta HTTP a una accion de controlador.
$routes = [
    'GET ' => [DashboardController::class, 'index'],
    'GET catalogo' => [CatalogoController::class, 'index'],
    'GET catalogo/curso' => [CatalogoController::class, 'detail'],
    'GET estudiante/acceso' => [AuthController::class, 'redirectLegacyLogin'],
    'POST estudiante/acceso' => [AuthController::class, 'authenticate'],
    'GET estudiante/registro' => [EstudianteAuthController::class, 'register'],
    'POST estudiante/registro' => [EstudianteAuthController::class, 'submitApplication'],
    'GET instructor/acceso' => [AuthController::class, 'redirectLegacyLogin'],
    'POST instructor/acceso' => [AuthController::class, 'authenticate'],
    'GET instructor' => [InstructorPortalController::class, 'index'],
    'GET mi-cuenta' => [CuentaEstudianteController::class, 'index'],
    'POST mi-cuenta/perfil' => [CuentaEstudianteController::class, 'updateProfile'],
    'POST mi-cuenta/inscribirse' => [CuentaEstudianteController::class, 'enroll'],
    'POST mi-cuenta/cancelar-solicitud' => [CuentaEstudianteController::class, 'cancelEnrollment'],
    'GET setup' => [AuthController::class, 'setup'],
    'POST setup' => [AuthController::class, 'createFirstAdministrator'],
    'GET login' => [AuthController::class, 'login'],
    'POST login' => [AuthController::class, 'authenticate'],
    'GET password/recuperar' => [AuthController::class, 'forgotPassword'],
    'POST password/recuperar' => [AuthController::class, 'requestPasswordReset'],
    'GET password/restablecer' => [AuthController::class, 'resetPasswordForm'],
    'POST password/restablecer' => [AuthController::class, 'resetPassword'],
    'POST logout' => [AuthController::class, 'logout'],
    'GET categorias' => [CategoriaController::class, 'index'],
    'POST categorias/crear' => [CategoriaController::class, 'create'],
    'POST categorias/actualizar' => [CategoriaController::class, 'update'],
    'POST categorias/estado' => [CategoriaController::class, 'setStatus'],
    'GET cursos' => [CursoController::class, 'index'],
    'POST cursos/crear' => [CursoController::class, 'create'],
    'POST cursos/actualizar' => [CursoController::class, 'update'],
    'POST cursos/estado' => [CursoController::class, 'setStatus'],
    'GET estudiantes' => [EstudianteController::class, 'index'],
    'POST estudiantes/crear' => [EstudianteController::class, 'create'],
    'POST estudiantes/actualizar' => [EstudianteController::class, 'update'],
    'POST estudiantes/contrasena' => [EstudianteController::class, 'setPassword'],
    'POST estudiantes/estado' => [EstudianteController::class, 'setStatus'],
    'POST estudiantes/eliminar' => [EstudianteController::class, 'delete'],
    'POST estudiantes/solicitud/estado' => [EstudianteController::class, 'reviewApplication'],
    'GET instructores' => [InstructorController::class, 'index'],
    'POST instructores/crear' => [InstructorController::class, 'create'],
    'POST instructores/actualizar' => [InstructorController::class, 'update'],
    'POST instructores/contrasena' => [InstructorController::class, 'setPassword'],
    'POST instructores/estado' => [InstructorController::class, 'setStatus'],
    'POST instructores/eliminar' => [InstructorController::class, 'delete'],
    'GET horarios' => [HorarioController::class, 'index'],
    'POST horarios/crear' => [HorarioController::class, 'create'],
    'POST horarios/actualizar' => [HorarioController::class, 'update'],
    'POST horarios/eliminar' => [HorarioController::class, 'delete'],
    'GET periodos' => [PeriodoController::class, 'index'],
    'POST periodos/crear' => [PeriodoController::class, 'create'],
    'POST periodos/actualizar' => [PeriodoController::class, 'update'],
    'POST periodos/eliminar' => [PeriodoController::class, 'delete'],
    'GET reportes' => [ReporteController::class, 'index'],
    'GET reportes/pdf' => [ReporteController::class, 'exportPdf'],
    'GET solicitudes' => [SolicitudController::class, 'index'],
    'POST solicitudes/crear' => [SolicitudController::class, 'create'],
    'POST solicitudes/estado' => [SolicitudController::class, 'changeStatus'],
];

// Ejecuta la ruta registrada o devuelve el error HTTP correspondiente.
if (isset($routes[$method . ' ' . $route])) {
    [$controller, $action] = $routes[$method . ' ' . $route];
    (new $controller())->$action();
    exit;
}

foreach ($routes as $routeDefinition => $_handler) {
    if (str_ends_with($routeDefinition, ' ' . $route)) {
        http_response_code(405);
        header('Allow: ' . substr($routeDefinition, 0, strpos($routeDefinition, ' ')));
        echo 'Método no permitido.';
        exit;
    }
}

http_response_code(404);
echo 'Página no encontrada.';
