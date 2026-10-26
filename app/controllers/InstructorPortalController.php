<?php

declare(strict_types=1);

/**
 * Prepara el panel privado con cursos, horarios y estudiantes del instructor autenticado.
 */
final class InstructorPortalController extends BaseController
{
    private Instructor $instructors;

    /** Prepara el modelo de instructores para cargar el portal privado. */
    public function __construct()
    {
        $this->instructors = new Instructor(getDatabaseConnection());
    }

    /** Muestra los cursos, horarios y estudiantes confirmados del instructor autenticado. */
    public function index(): void
    {
        $user = $this->requireInstructor();
        $courses = $this->instructors->coursesWithSchedules((int) $user['instructor_id']);
        $studentsByCourse = $this->instructors->confirmedStudentsByCourse((int) $user['instructor_id']);

        foreach ($courses as &$course) {
            $course['estudiantes'] = $studentsByCourse[$course['id_curso']] ?? [];
        }
        unset($course);

        $this->render('instructor/dashboard', [
            'title' => 'Portal del instructor',
            'user' => $user,
            'courses' => $courses,
            'csrfToken' => $this->csrfToken(),
        ]);
    }
}
