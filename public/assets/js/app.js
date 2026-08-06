(() => {
    'use strict';

    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('#main-nav');
    if (navToggle && nav) {
        navToggle.addEventListener('click', () => {
            const expanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!expanded));
            nav.classList.toggle('open', !expanded);
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || '¿Confirmar la operación?')) {
                event.preventDefault();
            }
        });
    });

    const adminCheckbox = document.querySelector('input[name="es_admin"]');
    const permissionList = document.querySelector('.permission-list');
    if (adminCheckbox && permissionList) {
        const syncPermissions = () => { permissionList.disabled = adminCheckbox.checked; };
        adminCheckbox.addEventListener('change', syncPermissions);
        syncPermissions();
    }

    const saleForm = document.querySelector('#sale-form');
    if (!saleForm) return;

    const linesContainer = saleForm.querySelector('#sale-lines');
    const template = saleForm.querySelector('#sale-line-template');
    const addButton = saleForm.querySelector('[data-add-sale-line]');
    const totalElement = saleForm.querySelector('[data-sale-total]');
    const formatter = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });

    const updateTotals = () => {
        let total = 0;
        linesContainer.querySelectorAll('[data-sale-line]').forEach((line) => {
            const select = line.querySelector('[data-product-select]');
            const quantity = line.querySelector('[data-quantity]');
            const option = select.options[select.selectedIndex];
            const price = Number(option?.dataset.price || 0);
            const amount = Math.max(0, Number(quantity.value || 0));
            const controlsStock = option?.dataset.controlsStock === '1';
            const stock = Number(option?.dataset.stock || 0);
            quantity.setCustomValidity(controlsStock && amount > stock ? `Stock disponible: ${stock}` : '');
            const subtotal = price * amount;
            total += subtotal;
            line.querySelector('[data-line-total]').textContent = formatter.format(subtotal);
        });
        totalElement.textContent = formatter.format(total);
    };

    linesContainer.addEventListener('input', updateTotals);
    linesContainer.addEventListener('change', updateTotals);
    linesContainer.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-sale-line]');
        if (!removeButton) return;
        const lines = linesContainer.querySelectorAll('[data-sale-line]');
        if (lines.length === 1) {
            lines[0].querySelector('[data-product-select]').value = '';
            lines[0].querySelector('[data-quantity]').value = '1';
        } else {
            removeButton.closest('[data-sale-line]').remove();
        }
        updateTotals();
    });
    addButton.addEventListener('click', () => {
        linesContainer.append(template.content.cloneNode(true));
        updateTotals();
    });
    updateTotals();
})();
