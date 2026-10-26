<?php

declare(strict_types=1);

/** Perfil y solicitudes de inscripcion del estudiante autenticado. */

$statusLabels = [
    'Pendiente' => 'Pendiente de revisión',
    'Confirmada' => 'Confirmada',
    'Cancelada' => 'Cancelada',
    'Rechazada' => 'Rechazada',
];
$statusClasses = [
    'Pendiente' => 'pending',
    'Confirmada' => 'approved',
    'Cancelada' => 'cancelled',
    'Rechazada' => 'rejected',
];
?>
<main class="student-portal">
        <header class="student-nav">
            <a class="student-brand" href="/"><span class="student-brand-mark" aria-hidden="true">FC</span><span>Formación Continua</span></a>
            <nav aria-label="Secciones">
                <a href="/catalogo">Catálogo</a>
                <a href="/mi-cuenta" aria-current="page">Mis inscripciones</a>
            </nav>
            <div class="student-session">
                <span class="student-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $student['nombres'], 0, 1, 'UTF-8') . mb_substr((string) $student['apellidos'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                <span><?= e(trim((string) $student['nombres'] . ' ' . (string) $student['apellidos'])) ?></span>
                <form action="/logout" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>
        </header>
        <div class="student-content">
        <header class="student-heading">
            <div><h1><?= e($title) ?></h1><p>Hola, <?= e((string) $student['nombres']) ?>. Estado de tus solicitudes.</p></div>
            <a class="student-button" href="/catalogo">Explorar catálogo</a>
        </header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>

        <section class="student-profile">
            <div class="section-heading"><div><p class="eyebrow">Datos de cuenta</p><h2>Mi perfil</h2></div><span class="student-id">Carnet: <?= e((string) $student['carnet']) ?></span></div>
            <form action="/mi-cuenta/perfil" method="post" class="student-profile-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Nombres<input maxlength="100" value="<?= e((string) $student['nombres']) ?>" readonly></label>
                <label>Apellidos<input maxlength="100" value="<?= e((string) $student['apellidos']) ?>" readonly></label>
                <label>Correo<input type="email" value="<?= e((string) $student['correo']) ?>" disabled><small>Para cambiar el correo, solicita apoyo a administración.</small></label>
                <!-- Validación del celular en el perfil del estudiante -->
                <label>Teléfono celular<input type="tel" name="telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="Opcional. Ingresa 8 dígitos; debe comenzar con 5, 6 o 7." placeholder="Ej. 71234567" value="<?= e((string) ($student['telefono'] ?? '')) ?>"><small>Opcional. 8 dígitos, comenzando con 5, 6 o 7.</small></label>
                <label>Edad (17 a 70 años)<input type="number" name="edad" min="17" max="70" step="1" required title="La edad debe estar entre 17 y 70 años." placeholder="Ej. 25" value="<?= e((string) ($student['edad'] ?? '')) ?>"></label>
                <label>Género
                    <select name="genero">
                        <option value="">Sin especificar</option>
                        <?php foreach (['Masculino', 'Femenino', 'Otro'] as $gender): ?>
                            <option value="<?= $gender ?>" <?= $student['genero'] === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button" type="submit">Guardar perfil</button>
            </form>
        </section>

        <section class="student-enrollments">
            <div class="section-heading"><div><p class="eyebrow">Procesos</p><h2>Mis solicitudes</h2></div></div>
            <div class="student-table-wrap">
                <table class="student-table">
                    <thead><tr><th>Curso</th><th>Modalidad</th><th>Fecha de solicitud</th><th>Estado</th><th>Motivo</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><a class="student-course" href="/catalogo/curso?id=<?= (int) $enrollment['id_curso'] ?>"><?= e((string) $enrollment['nombre_curso']) ?></a></td>
                                <td><?= e($enrollment['modalidad'] === 'Hibrido' ? 'Híbrido' : (string) $enrollment['modalidad']) ?></td>
                                <td><?= e((string) $enrollment['fecha_inscripcion']) ?></td>
                                <td><span class="student-status <?= e($statusClasses[$enrollment['estado_inscripcion']] ?? '') ?>"><?= e($statusLabels[$enrollment['estado_inscripcion']] ?? (string) $enrollment['estado_inscripcion']) ?></span></td>
                                <td>
                                    <?php if ($enrollment['estado_inscripcion'] === 'Rechazada'): ?>
                                        <?= e((string) ($enrollment['motivo_rechazo'] ?? 'Sin motivo indicado.')) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (in_array($enrollment['estado_inscripcion'], ['Pendiente', 'Confirmada'], true)): ?>
                                        <form action="/mi-cuenta/cancelar-solicitud" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="id_inscripcion" value="<?= (int) $enrollment['id_inscripcion'] ?>">
                                            <button class="student-button student-danger" type="submit" onclick="return confirm('¿Deseas darte de baja de este curso? El cupo quedará disponible.')"><?= $enrollment['estado_inscripcion'] === 'Confirmada' ? 'Darse de baja' : 'Cancelar solicitud' ?></button>
                                        </form>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($enrollments === []): ?><tr><td colspan="6" class="empty-state">Todavía no tienes solicitudes. <a href="/catalogo">Explora el catálogo</a>.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>
