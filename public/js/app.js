'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-module-form]');
    const openButton = document.querySelector('[data-open-form]');
    const closeButton = document.querySelector('[data-close-form]');
    const search = document.querySelector('[data-table-search]');
    const statusFilter = document.querySelector('[data-status-filter]');
    const emptyRow = document.querySelector('[data-empty-row]');
    const rejectionStatusSelects = document.querySelectorAll('[data-rejection-reason-select]');

    openButton?.addEventListener('click', () => form?.classList.remove('is-hidden'));
    closeButton?.addEventListener('click', () => form?.classList.add('is-hidden'));

    rejectionStatusSelects.forEach((select) => {
        const form = select.closest('form');
        const wrapper = form?.querySelector('[data-rejection-reason-wrapper]');
        const reason = form?.querySelector('[data-rejection-reason]');
        const updateRejectionReason = () => {
            const isRejected = select.value === 'Rechazada';
            if (wrapper) wrapper.hidden = !isRejected;
            if (reason) reason.required = isRejected;
        };

        select.addEventListener('change', updateRejectionReason);
        updateRejectionReason();
    });

    const filterRows = () => {
        const query = search?.value.toLowerCase() ?? '';
        const status = statusFilter?.value.toLowerCase() ?? '';
        const rows = document.querySelectorAll('[data-module-table] tbody tr:not([data-empty-row])');
        let visibleRows = 0;

        rows.forEach((row) => {
            const matchesQuery = row.textContent.toLowerCase().includes(query);
            const matchesStatus = !status || row.textContent.toLowerCase().includes(status);
            const visible = matchesQuery && matchesStatus;
            row.hidden = !visible;
            if (visible) visibleRows += 1;
        });

        if (emptyRow) emptyRow.hidden = visibleRows > 0;
    };

    search?.addEventListener('input', filterRows);
    statusFilter?.addEventListener('change', filterRows);

    document.querySelectorAll('.instructor-action-form').forEach((actionForm, index) => {
        const note = actionForm.querySelector('.instructor-field-note');
        const triggers = actionForm.querySelectorAll('.instructor-action-field select, > .button');
        if (!note || triggers.length === 0) return;

        const floatingNote = document.createElement('span');
        floatingNote.className = 'instructor-floating-note';
        floatingNote.textContent = note.textContent.trim();
        floatingNote.hidden = true;
        floatingNote.id = `instructor-note-${index + 1}`;
        document.body.append(floatingNote);
        triggers.forEach((trigger) => trigger.setAttribute('aria-describedby', floatingNote.id));
        let activeTrigger = triggers[0];

        const positionNote = () => {
            const triggerBounds = activeTrigger.getBoundingClientRect();
            const viewportPadding = 12;
            const noteWidth = Math.min(280, window.innerWidth - viewportPadding * 2);
            const left = Math.min(
                Math.max(viewportPadding, triggerBounds.left),
                window.innerWidth - noteWidth - viewportPadding
            );
            floatingNote.style.left = `${left}px`;
            floatingNote.style.width = `${noteWidth}px`;
            floatingNote.style.top = `${Math.max(viewportPadding, triggerBounds.top - floatingNote.offsetHeight - 8)}px`;
        };
        const showNote = (event) => {
            activeTrigger = event.currentTarget;
            floatingNote.hidden = false;
            positionNote();
        };
        const hideNote = (event) => {
            if (Array.from(triggers).includes(event.relatedTarget)) return;
            floatingNote.hidden = true;
        };

        triggers.forEach((trigger) => {
            trigger.addEventListener('mouseenter', showNote);
            trigger.addEventListener('focus', showNote);
            trigger.addEventListener('mouseleave', hideNote);
            trigger.addEventListener('blur', hideNote);
        });
        window.addEventListener('resize', positionNote);
        window.addEventListener('scroll', positionNote, true);

        if (note.classList.contains('instructor-field-note--persistent')) {
            floatingNote.hidden = false;
            positionNote();
        }
    });

    // Validar los celulares de estudiantes e instructores.
    // Permite únicamente números y un máximo de 8 dígitos.
    document.querySelectorAll(".celular-sv").forEach(function (campo) {
    campo.addEventListener("input", function () {
        campo.value = campo.value.replace(/\D/g, "").slice(0, 8);
    });
});

    // Validar fechas al crear y modificar períodos de inscripción.
    // La fecha de cierre no puede ser anterior a la fecha de inicio.
    document.querySelectorAll(".period-form, .period-edit-form")
    .forEach(function (formulario) {

        const inicio = formulario.querySelector(
            'input[name="fecha_inicio"]'
        );

        const cierre = formulario.querySelector(
            'input[name="fecha_cierre"]'
        );

        if (!inicio || !cierre) return;

        function validarFechas() {
            if (inicio.value && cierre.value &&
                cierre.value < inicio.value) {

                cierre.setCustomValidity(
                    "La fecha de cierre no puede ser anterior a la fecha de inicio."
                );

            } else {
                cierre.setCustomValidity("");
            }
        }

        inicio.addEventListener("input", validarFechas);
        cierre.addEventListener("input", validarFechas);

        formulario.addEventListener("submit", function (event) {
            validarFechas();

            if (!formulario.reportValidity()) {
                event.preventDefault();
            }
        });

        validarFechas();
    });
});
////////////////////////

// Validar nombres y apellidos.
// Permite letras, espacios, guiones y apóstrofes.
function permitirSoloLetras(event) {
    event.target.value = event.target.value.replace(
        /[^\p{L} '-]/gu,
        ""
    );
}
document.querySelectorAll(".soloLetras").forEach(function (campo) {
    campo.addEventListener("input", permitirSoloLetras);
});