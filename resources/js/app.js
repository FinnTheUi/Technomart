

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// Prevent accidental duplicate writes caused by double-clicking a submit button.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() === 'get') {
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';
    window.setTimeout(() => {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });
    }, 0);
}, true);

Alpine.start();
