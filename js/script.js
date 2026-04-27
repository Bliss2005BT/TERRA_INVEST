function toggleMenu() {
    const navLinks = document.querySelector('.nav-links');
    const hamburger = document.querySelector('.hamburger');

    if (!navLinks || !hamburger) {
        return;
    }

    const isOpen = navLinks.classList.contains('mobile-open');

    navLinks.classList.toggle('mobile-open', !isOpen);
    hamburger.classList.toggle('active', !isOpen);
    document.body.style.overflow = isOpen ? '' : 'hidden';
}

function login() {
    openAuthModal('login');
}

function register() {
    openAuthModal('register');
}

function openAuthModal(tab) {
    const modal = document.getElementById('auth-modal');

    if (!modal) {
        return;
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    switchTab(tab);
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');

    if (!modal) {
        return;
    }

    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function switchTab(tab) {
    const modalTitle = document.getElementById('modal-title');

    document.querySelectorAll('.tab-content').forEach((content) => {
        content.classList.remove('active');
    });

    document.querySelectorAll('.tab-btn').forEach((button) => {
        button.classList.remove('active');
    });

    const activeTab = document.getElementById(`${tab}-tab`);
    const activeButton = document.querySelector(`[onclick="switchTab('${tab}')"]`);

    if (activeTab) {
        activeTab.classList.add('active');
    }

    if (activeButton) {
        activeButton.classList.add('active');
    }

    if (modalTitle) {
        modalTitle.textContent = tab === 'login' ? 'Welcome Back' : 'Join Terra Invest';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setupNavigation();
    setupAuthModal();
    loadSessionData();
    showInitialPageMessages();
});

function setupNavigation() {
    document.querySelectorAll('.nav-item').forEach((link) => {
        link.addEventListener('click', () => {
            const navLinks = document.querySelector('.nav-links');
            const hamburger = document.querySelector('.hamburger');

            if (!navLinks || !hamburger) {
                return;
            }

            navLinks.classList.remove('mobile-open');
            hamburger.classList.remove('active');
            document.body.style.overflow = '';
        });
    });

    document.addEventListener('click', (event) => {
        const navLinks = document.querySelector('.nav-links');
        const hamburger = document.querySelector('.hamburger');

        if (!navLinks || !hamburger) {
            return;
        }

        if (
            navLinks.classList.contains('mobile-open')
            && !navLinks.contains(event.target)
            && !hamburger.contains(event.target)
        ) {
            navLinks.classList.remove('mobile-open');
            hamburger.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

function setupAuthModal() {
    const modal = document.getElementById('auth-modal');

    if (!modal) {
        return;
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeAuthModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('active')) {
            closeAuthModal();
        }
    });

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('.password-field');
            const input = field ? field.querySelector('input') : null;

            if (!input) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Hide' : 'Show';
            button.setAttribute('aria-label', `${isPassword ? 'Hide' : 'Show'} password`);
        });
    });

    document.querySelectorAll('.auth-form').forEach((form) => {
        // Fix: index.html uses `.auth-form` without `data-auth-form`, so bind by class as a fallback.
        form.addEventListener('submit', handleAuthSubmit);

        form.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', () => clearFieldError(form, input.name));
        });
    });
}

async function handleAuthSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    // Fix: infer the form type when `data-auth-form` is missing so login/register validation still runs.
    const formType = form.dataset.authForm
        || (form.querySelector('[name="confirm_password"]') ? 'register' : 'login');
    const formData = new FormData(form);

    // Pass redirect URL if set by the caller (e.g., services page)
    const redirectUrl = form.dataset.redirect;
    if (redirectUrl) {
        formData.append('redirect', redirectUrl);
    }

    const payload = Object.fromEntries(formData.entries());
    const errors = validateAuthForm(formType, payload);

    clearFormFeedback(form);

    if (Object.keys(errors).length > 0) {
        applyFormErrors(form, errors);
        setFormStatus(form, 'Please check your details and try again.', 'error');
        return;
    }

    setSubmitState(form, true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data || !data.success) {
            const serverErrors = data && typeof data.errors === 'object' ? data.errors : {};
            applyFormErrors(form, serverErrors);
            setFormStatus(
                form,
                data && data.message ? data.message : 'Something went wrong. Please try again.',
                'error'
            );
            return;
        }

        if (typeof data.redirect === 'string' && data.redirect !== '') {
            window.location.href = data.redirect;
            return;
        }

        setFormStatus(form, data.message || 'Success.', 'success');
    } catch (error) {
        console.error('Auth request failed:', error);
        setFormStatus(form, 'Something went wrong. Please try again.', 'error');
    } finally {
        setSubmitState(form, false);
    }
}

function validateAuthForm(formType, payload) {
    const errors = {};
    const email = String(payload.email || '').trim();
    const password = String(payload.password || '');
    const confirmPassword = String(payload.confirm_password || '');
    const name = String(payload.name || '').trim();

    if (formType === 'register') {
        if (name.length < 2) {
            errors.name = 'Enter your full name.';
        }
    }

    if (email === '') {
        errors.email = 'Enter your email address.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.email = 'Enter a valid email address.';
    }

    if (password === '') {
        errors.password = formType === 'register' ? 'Create a password.' : 'Enter your password.';
    } else if (
        formType === 'register'
        && (password.length < 8 || !/[A-Za-z]/.test(password) || !/\d/.test(password))
    ) {
        errors.password = 'Use at least 8 characters with letters and numbers.';
    }

    if (formType === 'register') {
        if (confirmPassword === '') {
            errors.confirm_password = 'Confirm your password.';
        } else if (password !== confirmPassword) {
            errors.confirm_password = 'Passwords do not match.';
        }
    }

    return errors;
}

function clearFormFeedback(form) {
    form.querySelectorAll('.form-group').forEach((group) => {
        group.classList.remove('has-error');
    });

    form.querySelectorAll('input').forEach((input) => {
        input.removeAttribute('aria-invalid');
        // Fix: clear previous browser validation messages so stale errors do not block resubmission.
        input.setCustomValidity('');
    });

    form.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });

    const status = form.querySelector('[data-form-status]');
    if (status) {
        status.hidden = true;
        status.textContent = '';
        status.className = 'auth-status';
    }
}

function clearFieldError(form, fieldName) {
    if (!fieldName) {
        return;
    }

    const input = form.querySelector(`[name="${fieldName}"]`);
    const group = input ? input.closest('.form-group') : null;
    const error = form.querySelector(`[data-error-for="${fieldName}"]`);

    if (group) {
        group.classList.remove('has-error');
    }

    if (input) {
        input.removeAttribute('aria-invalid');
        // Fix: remove custom validity when the user edits the field so native validation can recover.
        input.setCustomValidity('');
    }

    if (error) {
        error.textContent = '';
    }
}

function applyFormErrors(form, errors) {
    const entries = Object.entries(errors || {});

    entries.forEach(([fieldName, message], index) => {
        if (!message) {
            return;
        }

        const input = form.querySelector(`[name="${fieldName}"]`);
        const group = input ? input.closest('.form-group') : null;
        const error = form.querySelector(`[data-error-for="${fieldName}"]`);

        if (group) {
            group.classList.add('has-error');
        }

        if (input) {
            input.setAttribute('aria-invalid', 'true');
            // Fix: provide a native validation message when inline error markup is absent in index.html.
            input.setCustomValidity(String(message));
        }

        if (error) {
            error.textContent = message;
        }

        if (index === 0 && input) {
            input.focus();
            // Fix: surface the first error immediately even on the lightweight modal markup.
            input.reportValidity();
        }
    });
}

function setFormStatus(form, message, type) {
    const status = form.querySelector('[data-form-status]');

    if (!status) {
        // Fix: fall back to toasts when the page has no dedicated status region.
        showMessage(message, type);
        return;
    }

    if (!message) {
        status.hidden = true;
        status.textContent = '';
        status.className = 'auth-status';
        return;
    }

    status.hidden = false;
    status.textContent = message;
    status.className = `auth-status is-${type}`;
}

function setSubmitState(form, isLoading) {
    const button = form.querySelector('.auth-submit');
    const label = button ? button.querySelector('.auth-submit-label') : null;
    // Fix: infer the form type when `data-auth-form` is missing so button loading copy stays correct.
    const formType = form.dataset.authForm
        || (form.querySelector('[name="confirm_password"]') ? 'register' : 'login');

    if (!button) {
        return;
    }

    if (label) {
        if (!button.dataset.defaultLabel) {
            button.dataset.defaultLabel = label.textContent;
        }
    } else if (!button.dataset.defaultLabel) {
        // Fix: support buttons that use plain text instead of a nested `.auth-submit-label`.
        button.dataset.defaultLabel = button.textContent.trim();
    }

    button.disabled = isLoading;
    button.classList.toggle('is-loading', isLoading);

    const nextLabel = isLoading
        ? (formType === 'register' ? 'Creating Account...' : 'Signing In...')
        : button.dataset.defaultLabel;

    if (label) {
        label.textContent = nextLabel;
    } else {
        // Fix: update plain-text submit buttons when the richer label span is not present.
        button.textContent = nextLabel;
    }
}

function loadSessionData() {
    fetch('php/session_data.php', {
        headers: {
            Accept: 'application/json',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            window.userSession = data;
            updateNavbar();
        })
        .catch((error) => {
            console.error('Error loading session data:', error);
            window.userSession = { isLoggedIn: false, userName: null };
            updateNavbar();
        });
}

function updateNavbar() {
    const authButtons = document.getElementById('auth-buttons');

    if (!authButtons) {
        return;
    }

    if (window.userSession && window.userSession.isLoggedIn) {
        authButtons.innerHTML = `
            <span class="user-greeting">Welcome, ${escapeHtml(window.userSession.userName || 'User')}</span>
            <a href="php/logout.php" class="btn-logout">Logout</a>
        `;
        return;
    }

    authButtons.innerHTML = `
        <a href="#" class="btn-login" onclick="login(); return false;">Login</a>
        <a href="#" class="btn-start" onclick="register(); return false;">Start investing</a>
    `;
}

function showInitialPageMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');

    if (window.authPageFlash && window.authPageFlash.message) {
        showMessage(window.authPageFlash.message, window.authPageFlash.type || 'success');
    }

    if (success) {
        showMessage(success, 'success');
    }

    if (error) {
        showMessage(error, 'error');
    }
}

function showMessage(message, type) {
    if (!message) {
        return;
    }

    let container = document.querySelector('.toast-stack');

    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-stack';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type === 'success' ? 'success' : 'error'}`;
    toast.textContent = message;
    container.appendChild(toast);

    window.setTimeout(() => {
        toast.remove();
        if (!container.children.length) {
            container.remove();
        }
    }, 4500);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
