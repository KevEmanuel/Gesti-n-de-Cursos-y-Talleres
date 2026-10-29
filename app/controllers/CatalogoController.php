<?php

declare(strict_types=1);

/**
 * Coordina la consulta y presentacion del catalogo publico de cursos.
 */
final class CatalogoController extends BaseController
{
    private Curso $courses;
    private Categoria $categories;

    /** Prepara los modelos de cursos y categorías con una conexión compartida. */
    public function __construct()
    {
        $database = getDatabaseConnection();
        $this->courses = new Curso($database);
        $this->categories = new Categoria($database);
    }

    /** Filtra cursos públicos por búsqueda, categoría y Modalidad, y carga el catálogo. */
    public function index(): void
    {
        $csrfToken = $this->csrfToken();
        $filters = [
            'busqueda' => trim((string) ($_GET['busqueda'] ?? '')),
            'IdCategoria' => trim((string) ($_GET['IdCategoria'] ?? '')),
            'Modalidad' => trim((string) ($_GET['Modalidad'] ?? '')),
        ];
        $error = null;

        if ($filters['IdCategoria'] !== '' && !ctype_digit($filters['IdCategoria'])) {
            $error = 'Selecciona una categoría válida.';
        } elseif ($filters['Modalidad'] !== ''
            && !in_array($filters['Modalidad'], ['Presencial', 'Virtual', 'Hibrido'], true)) {
            $error = 'Selecciona una Modalidad válida.';
        } elseif (mb_strlen($filters['busqueda'], 'UTF-8') > 150) {
            $error = 'La búsqueda no puede exceder 150 caracteres.';
        }

        $this->render('catalogo', [
            'title' => 'Catálogo de cursos',
            'courses' => $error === null ? $this->courses->publicCatalog($filters) : [],
            'categories' => $this->categories->allActive(),
            'filters' => $filters,
            'error' => $error,
            'isStudent' => is_array($_SESSION['user'] ?? null)
                && ($_SESSION['user']['role'] ?? null) === 'student',
            'isInstructor' => is_array($_SESSION['user'] ?? null)
                && ($_SESSION['user']['role'] ?? null) === 'instructor',
            'isAdministrator' => is_array($_SESSION['user'] ?? null)
                && ($_SESSION['user']['role'] ?? null) === 'administrator',
            'csrfToken' => $csrfToken,
        ]);
    }

    /**
     * Muestra el detalle y los horarios de un curso disponible para consulta pública.
     */
    public function detail(): void
    {
        $csrfToken = $this->csrfToken();
        $courseId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $course = $courseId !== false && $courseId !== null && $courseId > 0
            ? $this->courses->publicCourse($courseId)
            : null;

        if ($course === null) {
            http_response_code(404);
            $this->render('catalogo/curso', [
                'title' => 'Curso no disponible',
                'course' => null,
                'schedules' => [],
                'canEnroll' => false,
                'isStudent' => false,
                'isInstructor' => is_array($_SESSION['user'] ?? null)
                    && ($_SESSION['user']['role'] ?? null) === 'instructor',
                'isAdministrator' => is_array($_SESSION['user'] ?? null)
                    && ($_SESSION['user']['role'] ?? null) === 'administrator',
                'activeEnrollment' => null,
                'csrfToken' => $csrfToken,
                'error' => null,
                'message' => null,
            ]);
            return;
        }

        $user = $_SESSION['user'] ?? null;
        $isStudent = is_array($user) && ($user['role'] ?? null) === 'student';
        $isInstructor = is_array($user) && ($user['role'] ?? null) === 'instructor';
        $isAdministrator = is_array($user) && ($user['role'] ?? null) === 'administrator';
        $canEnroll = $course['Estado'] === 'Activo'
            && (int) $course['Cupos_Disponibles'] > 0
            && $course['periodo_inicio'] !== null;
        $messages = [
            'solicitud_creada' => 'Tu solicitud fue registrada. El cupo quedó reservado mientras está pendiente.',
            'solicitud_cancelada' => 'Tu solicitud pendiente fue cancelada.',
        ];
        $errors = [
            'curso_no_disponible' => 'El curso ya no está disponible para inscripción.',
            'horario' => 'El horario de este curso se cruza con otra inscripción activa.',
            'periodo' => 'No hay un período de inscripción vigente para este curso.',
            'duplicada' => 'Ya tienes una solicitud activa para este curso.',
            'capacidad' => 'El curso alcanzó su capacidad máxima.',
            'solo_pendiente' => 'Solo puedes cancelar solicitudes que aún están pendientes.',
        ];

        $this->render('catalogo/curso', [
            'title' => (string) $course['Nombre_Curso'],
            'course' => $course,
            'schedules' => $this->courses->publicSchedule((int) $course['IdCurso']),
            'canEnroll' => $canEnroll,
            'isStudent' => $isStudent,
            'isInstructor' => $isInstructor,
            'isAdministrator' => $isAdministrator,
            'activeEnrollment' => $isStudent
                ? (new Solicitud(getDatabaseConnection()))->activeEnrollmentForStudent(
                    (int) $user['student_id'],
                    (int) $course['IdCurso']
                )
                : null,
            'csrfToken' => $csrfToken,
            'message' => $messages[(string) ($_GET['resultado'] ?? '')] ?? null,
            'error' => $errors[(string) ($_GET['error'] ?? '')] ?? null,
        ]);
    }
}
