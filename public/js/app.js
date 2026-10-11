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
});

//funciones para validar telefono
if (campoTelefono) {
    campoTelefono.addEventListener("input", function () {
        let numeros = campoTelefono.value.replace(/\D/g, "").slice(0, 8);

        if (numeros.length > 4) {
            numeros = numeros.slice(0, 4) + "-" + numeros.slice(4);
        }

        campoTelefono.value = numeros;
    });
}

//funcion para validar letras
function permitirSoloLetras(event) {
    event.target.value = event.target.value.replace(
        /[^A-Za-zÁÉÍÓÚáéíóúÜüÑñ\s]/g,
        ""
    );
}
document.querySelectorAll(".soloLetras").forEach(function (campo) {
    campo.addEventListener("input", permitirSoloLetras);
});