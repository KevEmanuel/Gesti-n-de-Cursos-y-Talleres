<?php

declare(strict_types=1);

/** Perfil y solicitudes de inscripcion del estudiante autenticado. */

$statusLabels = [
    'Pendiente' => 'Pendiente de revisión',
    'Confirmada' => 'Confirmada',
    'Cancelada' => 'Cancelada',
    'Rechazada' => 'Rechazada',
];
?>
<main class="container">
        <header class="page-header">
            <div><a href="/">Inicio</a><h1><?= e($title) ?></h1><p>Hola, <?= e($student['nombres']) ?>.</p></div>
            <div class="session-actions">
                <a class="button" href="/catalogo">Explorar cursos</a>
                <form action="/logout" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <button class="button button-secondary" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </header>
        <?php if ($message !== null): ?><p class="notice notice-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>

        <section class="card">
            <div class="section-heading"><div><p class="eyebrow">Datos de cuenta</p><h2>Mi perfil</h2></div><span class="student-id">Carnet: <?= e((string) $student['carnet']) ?></span></div>
            <form action="/mi-cuenta/perfil" method="post" class="student-profile-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label>Nombres<input maxlength="100" value="<?= e((string) $student['nombres']) ?>" readonly></label>
                <label>Apellidos<input maxlength="100" value="<?= e((string) $student['apellidos']) ?>" readonly></label>
                <label>Correo<input type="email" value="<?= e((string) $student['correo']) ?>" disabled><small>Para cambiar el correo, solicita apoyo a administración.</small></label>
                <!-- Validación del celular en el perfil del estudiante -->
                <label>Teléfono<input type="tel" name="telefono" class="celular-sv" inputmode="numeric" maxlength="8" pattern="[567][0-9]{7}" title="El celular debe tener 8 dígitos y comenzar con 5, 6 o 7" value="<?= e((string) ($student['telefono'] ?? '')) ?>"></label>
                <label>Edad<input type="number" name="edad" min="0" max="150" value="<?= e((string) ($student['edad'] ?? '')) ?>"></label>
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

        <section class="card profile-list">
            <div class="section-heading"><div><p class="eyebrow">Procesos</p><h2>Mis solicitudes</h2></div></div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Curso</th><th>Modalidad</th><th>Fecha de solicitud</th><th>Estado</th><th>Motivo</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><a href="/catalogo/curso?id=<?= (int) $enrollment['id_curso'] ?>"><?= e((string) $enrollment['nombre_curso']) ?></a></td>
                                <td><?= e($enrollment['modalidad'] === 'Hibrido' ? 'Híbrido' : (string) $enrollment['modalidad']) ?></td>
                                <td><?= e((string) $enrollment['fecha_inscripcion']) ?></td>
                                <td><span class="badge <?= $enrollment['estado_inscripcion'] === 'Confirmada' ? 'badge-available' : 'badge-muted' ?>"><?= e($statusLabels[$enrollment['estado_inscripcion']] ?? (string) $enrollment['estado_inscripcion']) ?></span></td>
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
                                            <button class="button button-secondary" type="submit" onclick="return confirm('¿Deseas darte de baja de este curso? El cupo quedará disponible.')"><?= $enrollment['estado_inscripcion'] === 'Confirmada' ? 'Darse de baja' : 'Cancelar solicitud' ?></button>
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
    </main>
