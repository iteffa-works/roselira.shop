(function () {
    var shell = document.querySelector('[data-admin-shell]');
    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.querySelector('[data-sidebar-overlay]');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var closeBtn = document.querySelector('[data-sidebar-close]');

    if (!shell || !sidebar || !overlay || !toggle) {
        return;
    }

    function setOpen(open) {
        shell.classList.toggle('admin-sidebar-open', open);
        sidebar.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Закрити меню' : 'Відкрити меню');
    }

    toggle.addEventListener('click', function () {
        setOpen(!shell.classList.contains('admin-sidebar-open'));
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });
    }

    overlay.addEventListener('click', function () {
        setOpen(false);
    });

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            setOpen(false);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    window.addEventListener('resize', function () {
        if (window.matchMedia('(min-width: 901px)').matches) {
            setOpen(false);
        }
    });
})();
