<?php

declare(strict_types=1);

/**
 * Coordina las operaciones privadas de perfil e inscripcion del estudiante autenticado.
 */
final class CuentaEstudianteController extends BaseController
{
    private Estudiante $students;
    private Solicitud $requests;

    public function __construct()
    {
        $database = getDatabaseConnection();
        $this->students = new Estudiante($database);
        $this->requests = new Solicitud($database);
    }

    /**
     * Valida el acceso, obtiene los datos necesarios y muestra la vista del modulo.
     */
    public function index(): void
    {
        $user = $this->requireStudent();
        $student = $this->students->findForAccount((int) $user['student_id']);

        if ($student === null) {
            http_response_code(403);
            echo 'La cuenta de estudiante no está disponible.';
            return;
        }

        $messages = [
            'perfil_actualizado' => 'Tus datos fueron actualizados.',
            'solicitud_creada' => 'La solicitud quedó registrada como pendiente.',
            'solicitud_cancelada' => 'Te diste de baja del curso y el cupo quedó disponible.',
        ];
        $errors = [
            'perfil_invalido' => 'Revisa los datos del perfil; algunos campos son obligatorios o no válidos.',
            'curso_no_disponible' => 'El curso ya no está disponible para inscripción.',
            'horario' => 'El horario del curso se cruza con otra inscripción activa.',
            'periodo' => 'No hay un período de inscripción vigente.',
            'duplicada' => 'Ya tienes una solicitud activa para este curso.',
            'capacidad' => 'El curso alcanzó su capacidad máxima.',
            'solo_pendiente' => 'Solo puedes darte de baja de solicitudes pendientes o inscripciones activas.',
        ];

        $this->render('estudiante/cuenta', [
            'title' => 'Mi cuenta',
            'student' => $student,
            'enrollments' => $this->requests->forStudent((int) $user['student_id']),
            'csrfToken' => $this->csrfToken(),
            'message' => $messages[(string) ($_GET['resultado'] ?? '')] ?? null,
            'error' => $errors[(string) ($_GET['error'] ?? '')] ?? null,
        ]);
    }

    /**
     * Valida y actualiza los datos editables del perfil del estudiante.
     */
    public function updateProfile(): void
    {
        $user = $this->requireStudent();
        $this->verifyCsrfToken();
        $studentId = (int) $user['student_id'];
        $data = [
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'edad' => trim((string) ($_POST['edad'] ?? '')),
            'genero' => trim((string) ($_POST['genero'] ?? '')),
        ];

        if (strlen($data['telefono']) > 30) {
            $this->redirect('/mi-cuenta?error=perfil_invalido');
        }
        if ($data['edad'] !== '' && (!ctype_digit($data['edad']) || (int) $data['edad'] > 150)) {
            $this->redirect('/mi-cuenta?error=perfil_invalido');
        }
        if ($data['genero'] !== '' && !in_array($data['genero'], ['Masculino', 'Femenino', 'Otro'], true)) {
            $this->redirect('/mi-cuenta?error=perfil_invalido');
        }

        $data['telefono'] = $data['telefono'] === '' ? null : $data['telefono'];
        $data['edad'] = $data['edad'] === '' ? null : (int) $data['edad'];
        $data['genero'] = $data['genero'] === '' ? null : $data['genero'];
        if (!$this->students->updateOwnProfile($studentId, $data)) {
            http_response_code(403);
            echo 'La cuenta de estudiante no está disponible.';
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->csrfToken();
        }
        $this->redirect('/mi-cuenta?resultado=perfil_actualizado');
    }

    /**
     * Registra una solicitud de inscripcion para el estudiante autenticado.
     */
    public function enroll(): void
    {
        $user = $this->requireStudent();
        $this->verifyCsrfToken();
        $courseId = filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT);

        if ($courseId === false || $courseId === null || $courseId < 1) {
            http_response_code(400);
            echo 'Identificador de curso no válido.';
            return;
        }

        try {
            $this->requests->createPending((int) $user['student_id'], $courseId);
        } catch (DomainException $exception) {
            $error = match (true) {
                str_contains($exception->getMessage(), 'horario') => 'horario',
                str_contains($exception->getMessage(), 'período') => 'periodo',
                str_contains($exception->getMessage(), 'capacidad') => 'capacidad',
                str_contains($exception->getMessage(), 'activa') => 'duplicada',
                default => 'curso_no_disponible',
            };
            $this->redirect('/catalogo/curso?id=' . $courseId . '&error=' . $error);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->redirect('/catalogo/curso?id=' . $courseId . '&error=duplicada');
            }
            throw $exception;
        }

        $this->redirect('/catalogo/curso?id=' . $courseId . '&resultado=solicitud_creada');
    }

    /**
     * Cancela la inscripcion seleccionada por el estudiante autenticado.
     */
    public function cancelEnrollment(): void
    {
        $user = $this->requireStudent();
        $this->verifyCsrfToken();
        $enrollmentId = filter_var($_POST['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);

        if ($enrollmentId === false || $enrollmentId === null || $enrollmentId < 1) {
            http_response_code(400);
            echo 'Identificador de inscripción no válido.';
            return;
        }

        try {
            if (!$this->requests->cancelActiveForStudent($enrollmentId, (int) $user['student_id'])) {
                http_response_code(404);
                echo 'La solicitud solicitada no existe.';
                return;
            }
        } catch (DomainException) {
            $this->redirect('/mi-cuenta?error=solo_pendiente');
        }

        $this->redirect('/mi-cuenta?resultado=solicitud_cancelada');
    }
}
