

import Alpine from 'alpinejs';
import '../css/gcash-payments.css';
import './gcash-payments';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

const ready = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
        callback();
    }
};

const ensureExperienceShell = () => {
    const main = document.querySelector('main') || document.querySelector('.main');
    if (main && !main.id) main.id = 'main-content';
    if (main && main.tagName !== 'MAIN') main.setAttribute('role', 'main');

    if (main && !document.querySelector('.skip-link')) {
        const skipLink = document.createElement('a');
        skipLink.className = 'skip-link';
        skipLink.href = '#main-content';
        skipLink.textContent = 'Skip to main content';
        document.body.prepend(skipLink);
    }

    document.querySelectorAll('.alert').forEach((alert) => {
        alert.setAttribute('role', alert.classList.contains('alert-error') ? 'alert' : 'status');
        alert.setAttribute('aria-live', 'polite');
    });

    document.querySelectorAll('table').forEach((table) => {
        if (!table.parentElement?.classList.contains('table-scroll')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-scroll';
            wrapper.tabIndex = 0;
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', 'Scrollable data table');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    const sidebar = document.querySelector('.sidebar');
    if (sidebar && !document.querySelector('.mobile-nav-toggle')) {
        sidebar.id = sidebar.id || 'application-sidebar';
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'mobile-nav-toggle';
        toggle.setAttribute('aria-controls', sidebar.id);
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation');
        toggle.innerHTML = '<span></span><span></span><span></span>';

        const overlay = document.createElement('button');
        overlay.type = 'button';
        overlay.className = 'sidebar-overlay';
        overlay.setAttribute('aria-label', 'Close navigation');

        const closeNavigation = () => {
            document.body.classList.remove('sidebar-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Open navigation');
        };

        toggle.addEventListener('click', () => {
            const open = document.body.classList.toggle('sidebar-open');
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        });
        overlay.addEventListener('click', closeNavigation);
        sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeNavigation));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeNavigation();
        });

        document.body.append(toggle, overlay);
    }
};

const confirmationDialog = (() => {
    let lastFocus = null;
    const dialog = document.createElement('dialog');
    dialog.className = 'confirm-dialog';
    dialog.innerHTML = `
        <form method="dialog" class="confirm-dialog-card">
            <div class="confirm-dialog-icon" aria-hidden="true">!</div>
            <div><h2>Confirm action</h2><p data-confirm-message></p></div>
            <div class="confirm-dialog-actions">
                <button value="cancel" class="btn btn-outline">Cancel</button>
                <button value="confirm" class="btn btn-danger">Continue</button>
            </div>
        </form>`;

    ready(() => document.body.appendChild(dialog));
    dialog.addEventListener('close', () => lastFocus?.focus());

    return (message) => new Promise((resolve) => {
        lastFocus = document.activeElement;
        dialog.querySelector('[data-confirm-message]').textContent = message;
        dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true });
        dialog.showModal();
    });
})();

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.confirmed === 'true') return;

    const handler = form.getAttribute('onsubmit') || '';
    const explicitMessage = form.dataset.confirm;
    const legacyMessage = handler.match(/confirm\((['"])(.*?)\1\)/)?.[2];
    const message = explicitMessage || legacyMessage;
    if (!message) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    if (await confirmationDialog(message)) {
        form.dataset.confirmed = 'true';
        form.removeAttribute('onsubmit');
        form.requestSubmit(event.submitter || undefined);
    }
}, true);

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
    form.setAttribute('aria-busy', 'true');
    window.setTimeout(() => {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            if (button instanceof HTMLButtonElement && !button.dataset.originalLabel) {
                button.dataset.originalLabel = button.textContent.trim();
                button.textContent = 'Working…';
            }
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });
    }, 0);
}, true);

ready(ensureExperienceShell);

Alpine.start();
