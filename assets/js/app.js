// Small UI enhancements — the app works fully without JavaScript.
(function () {
    // Show / hide password
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('is-shown', show);
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            input.focus();
        });
    });

    // Password strength meter
    var strengthInput = document.querySelector('[data-strength-input]');
    var meter = document.querySelector('[data-strength]');
    if (strengthInput && meter) {
        var label = meter.querySelector('.strength-label');
        var labels = ['At least 8 characters, including a letter and a number', 'Weak', 'Fair', 'Good', 'Strong'];
        strengthInput.addEventListener('input', function () {
            var v = strengthInput.value, score = 0;
            if (v.length >= 8) score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v) || v.length >= 14) score++;
            if (v.length > 0 && score === 0) score = 1;
            meter.dataset.level = v.length ? score : 0;
            label.textContent = labels[v.length ? score : 0];
        });
    }

    // Loading state on submit
    document.querySelectorAll('[data-loading-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.classList.add('is-loading');
        });
    });

    // Dismissible alerts (auto-hide success messages)
    function hide(alert) {
        alert.classList.add('is-hiding');
        setTimeout(function () { alert.remove(); }, 300);
    }
    document.querySelectorAll('[data-dismiss]').forEach(function (btn) {
        btn.addEventListener('click', function () { hide(btn.closest('.alert')); });
    });
    document.querySelectorAll('.alert-success[data-autodismiss]').forEach(function (alert) {
        setTimeout(function () { hide(alert); }, 5000);
    });

    // Mobile sidebar
    var sidebar = document.getElementById('sidebar');
    document.querySelectorAll('[data-toggle-sidebar]').forEach(function (btn) {
        btn.addEventListener('click', function () { sidebar.classList.add('is-open'); });
    });
    document.querySelectorAll('[data-close-sidebar], .sidebar .nav-link[href^="#"]').forEach(function (el) {
        el.addEventListener('click', function () { sidebar && sidebar.classList.remove('is-open'); });
    });

    // Highlight active nav link
    var links = document.querySelectorAll('.nav-link[href^="#"]');
    links.forEach(function (link) {
        link.addEventListener('click', function () {
            links.forEach(function (l) { l.classList.remove('active'); });
            link.classList.add('active');
        });
    });

    // User dropdown
    document.querySelectorAll('[data-dropdown]').forEach(function (menu) {
        var toggle = menu.querySelector('[data-dropdown-toggle]');
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open);
        });
        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target)) {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
        menu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { menu.classList.remove('is-open'); });
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[data-dropdown].is-open').forEach(function (m) { m.classList.remove('is-open'); });
        if (sidebar) sidebar.classList.remove('is-open');
    });
})();
