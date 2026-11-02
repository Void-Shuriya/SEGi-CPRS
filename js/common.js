(() => {
    'use strict';
    const configElement = document.getElementById('session-config');
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirm;
            if (message && !window.confirm(message)) {
                event.preventDefault();
                return;
            }
        });
        // Associate native validation feedback with the actual input.
        form.querySelectorAll('input, select, textarea').forEach((input) => {
            input.addEventListener('invalid', () => {
                input.setAttribute('aria-invalid', 'true');
                let hint = document.getElementById(input.id + '-error');
                if (!hint && input.id) {
                    hint = document.createElement('span');
                    hint.id = input.id + '-error';
                    hint.className = 'field-error';
                    input.insertAdjacentElement('afterend', hint);
                    input.setAttribute('aria-describedby', hint.id);
                }
                if (hint) hint.textContent = input.validationMessage;
            });
            input.addEventListener('input', () => {
                if (input.validity.valid) {
                    input.removeAttribute('aria-invalid');
                    const hint = document.getElementById(input.id + '-error');
                    if (hint) hint.textContent = '';
                }
            });
        });
    });
    const alert = document.querySelector('.alert-danger');
    if (alert) {
        alert.tabIndex = -1;
        alert.focus();
    }
    if (!configElement) return;
    const config = JSON.parse(configElement.textContent);
    const dialog = document.getElementById('session-warning');
    const countdown = document.getElementById('session-countdown');
    const status = document.getElementById('session-status');
    const continueButton = document.getElementById('continue-session');
    let expiresAt = Date.now() + config.remaining * 1000;
    let lastContact = 0;
    let busy = false;
    let pendingActivity = false;
    let previousFocus;
    function expire() {
        window.location.replace('index.php?expired=1');
    }
    async function contact(active) {
        if (busy) return;
        busy = true;
        lastContact = Date.now();
        try {
            const options = { credentials: 'same-origin', cache: 'no-store' };
            if (active) {
                options.method = 'POST';
                options.body = new URLSearchParams({ csrf_token: config.csrf });
            }
            const response = await fetch('session_activity.php', options);
            if (response.status === 401) {
                expire();
                return;
            }
            if (!response.ok) throw new Error('Session unavailable');
            const result = await response.json();
            expiresAt = Date.now() + result.remaining * 1000;
            if (active) pendingActivity = false;
            status.textContent = '';
            if (dialog.open && result.remaining > 120) {
                dialog.close();
                if (previousFocus?.isConnected) previousFocus.focus();
            }
        } catch {
            if (dialog.open) status.textContent = 'Could not reach the server. Try Continue Session again.';
        } finally {
            busy = false;
            continueButton.disabled = false;
        }
    }
    function activity(event) {
        if (!event.isTrusted || dialog.open) return;
        pendingActivity = true;
        if (Date.now() - lastContact > 20000) contact(true);
    }
    ['keydown', 'pointerdown', 'input', 'scroll'].forEach((name) =>
        document.addEventListener(name, activity, { passive: true, capture: true })
    );
    continueButton.addEventListener('click', () => {
        continueButton.disabled = true;
        contact(true);
    });
    dialog.addEventListener('cancel', (event) => event.preventDefault());
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) contact(false);
    });
    setInterval(() => {
        if (Date.now() - lastContact >= 30000) contact(pendingActivity && !dialog.open);
        const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
        countdown.textContent = Math.floor(remaining / 60) + ':' + String(remaining % 60).padStart(2, '0');
        if (remaining <= 0) {
            expire();
            return;
        }
        if (remaining <= 120 && !dialog.open) {
            previousFocus = document.activeElement;
            dialog.showModal();
            continueButton.focus();
        }
    }, 1000);
})();
