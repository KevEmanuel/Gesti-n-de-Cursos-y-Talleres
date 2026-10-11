<?php

declare(strict_types=1);

// Carga dependencias de la aplicacion y entrega la solicitud al enrutador.
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/models/BaseModel.php';
require_once __DIR__ . '/../app/models/Identificador.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Estudiante.php';
require_once __DIR__ . '/../app/models/SolicitudEstudiante.php';
require_once __DIR__ . '/../app/models/Instructor.php';
require_once __DIR__ . '/../app/models/Curso.php';
require_once __DIR__ . '/../app/models/Horario.php';
require_once __DIR__ . '/../app/models/Periodo.php';
require_once __DIR__ . '/../app/models/Reporte.php';
require_once __DIR__ . '/../app/models/Solicitud.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/CategoriaController.php';
require_once __DIR__ . '/../app/controllers/CursoController.php';
require_once __DIR__ . '/../app/controllers/EstudianteController.php';
require_once __DIR__ . '/../app/controllers/InstructorController.php';
require_once __DIR__ . '/../app/controllers/HorarioController.php';
require_once __DIR__ . '/../app/controllers/PeriodoController.php';
require_once __DIR__ . '/../app/controllers/ReporteController.php';
require_once __DIR__ . '/../app/controllers/SolicitudController.php';
require_once __DIR__ . '/../app/controllers/CatalogoController.php';
require_once __DIR__ . '/../app/controllers/EstudianteAuthController.php';
require_once __DIR__ . '/../app/controllers/InstructorPortalController.php';
require_once __DIR__ . '/../app/controllers/CuentaEstudianteController.php';
require_once __DIR__ . '/../routes/web.php';
