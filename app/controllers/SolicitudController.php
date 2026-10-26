<?php

declare(strict_types=1);

/**
 * Coordina la creacion y revision administrativa de inscripciones.
 */
final class SolicitudController extends BaseController
{
    private Solicitud $requests;

    /** Prepara el modelo que gestiona solicitudes de inscripción y cupos. */
    public function __construct()
    {
        $this->requests = new Solicitud(getDatabaseConnection());
    }

    /** Muestra las inscripciones y opciones administrativas para su revisión. */
    public function index(): void
    {
        $this->requireAdministrator();
        $this->render('solicitudes', $this->viewData());
    }

    /** Registra una inscripción pendiente para un estudiante y curso seleccionados. */
    public function create(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $studentId = filter_var($_POST['id_estudiante'] ?? null, FILTER_VALIDATE_INT);
        $courseId = filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT);

        if ($studentId === false || $studentId === null || $studentId < 1
            || $courseId === false || $courseId === null || $courseId < 1) {
            $this->renderError('Selecciona un estudiante y un curso válidos.');
            return;
        }

        try {
            $this->requests->createPending(
                $studentId,
                $courseId,
                (int) $_SESSION['user']['administrator_id']
            );
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage());
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('El estudiante ya tiene una solicitud activa para este curso.');
                return;
            }
            throw $exception;
        }

        $this->redirect('/solicitudes?mensaje=creada');
    }

    /**
     * Cambia el estado de la solicitud dentro de una transaccion.
     */
    public function changeStatus(): void
    {
        $this->requireAdministrator();
        $this->verifyCsrfToken();
        $id = filter_var($_POST['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['estado_inscripcion'] ?? '');
        $rejectionReason = trim((string) ($_POST['motivo_rechazo'] ?? ''));

        if ($id === false || $id === null || $id < 1
            || !in_array($status, ['Pendiente', 'Confirmada', 'Cancelada', 'Rechazada'], true)) {
            http_response_code(400);
            echo 'Los datos enviados para actualizar la solicitud no son válidos.';
            return;
        }

        try {
            if (!$this->requests->changeStatus($id, $status, $rejectionReason)) {
                http_response_code(404);
                echo 'La solicitud solicitada no existe.';
                return;
            }
        } catch (DomainException $exception) {
            $this->renderError($exception->getMessage());
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderError('No se puede reactivar esta inscripción porque el estudiante ya tiene una inscripción activa en el curso.');
                return;
            }
            throw $exception;
        }

        $this->redirect('/solicitudes?mensaje=actualizada');
    }

    /** Obtiene inscripciones, estudiantes, cursos y mensajes para la vista. */
    private function viewData(): array
    {
        $messages = [
            'creada' => 'Solicitud registrada y cupo reservado.',
            'actualizada' => 'Estado de la solicitud actualizado.',
            'estado_actualizada' => 'Estado de la solicitud actualizado.',
        ];

        return [
            'title' => 'Solicitudes de inscripción',
            'requests' => $this->requests->all(),
            'students' => $this->requests->students(),
            'courses' => $this->requests->courses(),
            'csrfToken' => $this->csrfToken(),
            'message' => $messages[(string) ($_GET['mensaje'] ?? '')] ?? null,
            'error' => null,
        ];
    }

    /** Renderiza nuevamente las inscripciones mostrando el error de validación. */
    private function renderError(string $error): void
    {
        http_response_code(422);
        $data = $this->viewData();
        $data['error'] = $error;
        $data['message'] = null;
        $this->render('solicitudes', $data);
    }
}
