<?php

declare(strict_types=1);

/**
 * Coordina la consulta y presentacion del catalogo publico de cursos.
 */
final class CatalogoController extends BaseController
{
    private Curso $courses;
    private Categoria $categories;

    public function __construct()
    {
        $database = getDatabaseConnection();
        $this->courses = new Curso($database);
        $this->categories = new Categoria($database);
    }

    /**
     * Valida el acceso, obtiene los datos necesarios y muestra la vista del modulo.
     */
    public function index(): void
    {
        $csrfToken = $this->csrfToken();
        $filters = [
            'busqueda' => trim((string) ($_GET['busqueda'] ?? '')),
            'id_categoria' => trim((string) ($_GET['id_categoria'] ?? '')),
            'modalidad' => trim((string) ($_GET['modalidad'] ?? '')),
        ];
        $error = null;

        if ($filters['id_categoria'] !== '' && !ctype_digit($filters['id_categoria'])) {
            $error = 'Selecciona una categoría válida.';
        } elseif ($filters['modalidad'] !== ''
            && !in_array($filters['modalidad'], ['Presencial', 'Virtual', 'Hibrido'], true)) {
            $error = 'Selecciona una modalidad válida.';
        } elseif (strlen($filters['busqueda']) > 150) {
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
     * Muestra la informacion detallada de un curso publicado.
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
        $canEnroll = $course['estado'] === 'Activo'
            && (int) $course['cupos_disponibles'] > 0
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
            'title' => (string) $course['nombre_curso'],
            'course' => $course,
            'schedules' => $this->courses->publicSchedule((int) $course['id_curso']),
            'canEnroll' => $canEnroll,
            'isStudent' => $isStudent,
            'isInstructor' => $isInstructor,
            'isAdministrator' => $isAdministrator,
            'activeEnrollment' => $isStudent
                ? (new Solicitud(getDatabaseConnection()))->activeEnrollmentForStudent(
                    (int) $user['student_id'],
                    (int) $course['id_curso']
                )
                : null,
            'csrfToken' => $csrfToken,
            'message' => $messages[(string) ($_GET['resultado'] ?? '')] ?? null,
            'error' => $errors[(string) ($_GET['error'] ?? '')] ?? null,
        ]);
    }
}
