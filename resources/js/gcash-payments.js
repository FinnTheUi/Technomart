const initializeGcash = () => {
    const openers = new WeakMap();
    document.querySelectorAll('.gcash-dialog').forEach(dialog => {
        document.body.appendChild(dialog);
        dialog.addEventListener('close', () => {
            document.documentElement.classList.toggle('gcash-modal-open', !!document.querySelector('.gcash-dialog[open]'));
            openers.get(dialog)?.focus();
        });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
        });
    });
    const open = (dialog, trigger) => {
        if (!dialog || dialog.open) return;
        openers.set(dialog, trigger || document.activeElement);
        dialog.showModal();
        document.documentElement.classList.add('gcash-modal-open');
    };
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-gcash-open]');
        if (trigger) open(document.getElementById(trigger.dataset.gcashOpen), trigger);
        if (event.target.closest('[data-gcash-close]')) event.target.closest('dialog')?.close();
    });
    document.querySelectorAll('[data-gcash-recipient-select]').forEach(select => {
        select.addEventListener('change', () => {
            select.closest('.gcash-receiving-details').querySelectorAll('[data-gcash-recipient]').forEach(panel => {
                panel.hidden = panel.dataset.gcashRecipient !== select.value;
            });
        });
    });
    document.querySelectorAll('.gcash-dialog[data-gcash-reopen]').forEach(dialog => open(dialog));
};
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeGcash, { once: true });
else initializeGcash();
