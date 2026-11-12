/* ============================================================
   LABSYNC - MASTER JAVASCRIPT
   Version 2.1

   Vanilla JavaScript
   No external JavaScript dependencies
============================================================ */

(function () {

    'use strict';


    /* ========================================================
       01. HELPER FUNCTIONS
    ======================================================== */

    function $(selector, root) {
        return (root || document).querySelector(selector);
    }

    function $$(selector, root) {
        return Array.prototype.slice.call(
            (root || document).querySelectorAll(selector)
        );
    }


    /* ========================================================
       02. PUBLIC NAVIGATION
    ======================================================== */

    function initPublicNav() {

        var navbar = document.querySelector('.navbar-container');
        var siteNav = document.querySelector('.nav-links');
        var navActions = document.querySelector('.nav-actions');

        if (!navbar || !siteNav) return;

        if (!siteNav.id) {
            siteNav.id = 'siteNav';
        }

        var navToggle = document.getElementById('navToggle');

        if (!navToggle) {

            navToggle = document.createElement('button');

            navToggle.type = 'button';
            navToggle.id = 'navToggle';
            navToggle.className = 'nav-toggle';
            navToggle.textContent = '☰';

            navToggle.setAttribute('aria-label', 'Open navigation');
            navToggle.setAttribute('aria-controls', siteNav.id);
            navToggle.setAttribute('aria-expanded', 'false');

            navbar.insertBefore(navToggle, siteNav);
        }

        function openNavigation() {

            navbar.classList.add('nav-open');

            navToggle.setAttribute('aria-expanded', 'true');
            navToggle.setAttribute('aria-label', 'Close navigation');

            navToggle.textContent = '✕';
        }

        function closeNavigation() {

            navbar.classList.remove('nav-open');

            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.setAttribute('aria-label', 'Open navigation');

            navToggle.textContent = '☰';
        }

        navToggle.addEventListener('click', function () {

            if (navbar.classList.contains('nav-open')) {
                closeNavigation();
            } else {
                openNavigation();
            }

        });

        $$('.nav-links a', navbar).forEach(function (link) {
            link.addEventListener('click', closeNavigation);
        });

        if (navActions) {

            $$('a', navActions).forEach(function (link) {
                link.addEventListener('click', closeNavigation);
            });

        }

        document.addEventListener('click', function (event) {

            if (
                navbar.classList.contains('nav-open') &&
                !navbar.contains(event.target)
            ) {
                closeNavigation();
            }

        });

        document.addEventListener('keydown', function (event) {

            if (event.key === 'Escape') {
                closeNavigation();
            }

        });

        window.addEventListener('resize', function () {

            if (window.innerWidth > 991) {
                closeNavigation();
            }

        });

    }


    /* ========================================================
       03. DASHBOARD SIDEBAR
    ======================================================== */

    function initSidebar() {

        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('sidebar');

        if (!sidebarToggle || !sidebar) return;

        var backdrop = document.querySelector('.sidebar-backdrop');

        if (!backdrop) {

            backdrop = document.createElement('div');

            backdrop.className = 'sidebar-backdrop';
            backdrop.setAttribute('aria-hidden', 'true');

            document.body.appendChild(backdrop);
        }

        function openSidebar() {

            sidebar.classList.add('open');
            backdrop.classList.add('active');

            sidebarToggle.setAttribute('aria-expanded', 'true');
        }

        function closeSidebar() {

            sidebar.classList.remove('open');
            backdrop.classList.remove('active');

            sidebarToggle.setAttribute('aria-expanded', 'false');
        }

        sidebarToggle.addEventListener('click', function () {

            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }

        });

        backdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (event) {

            if (event.key === 'Escape') {
                closeSidebar();
            }

        });

        $$('.sidebar a', sidebar).forEach(function (link) {

            link.addEventListener('click', function () {

                if (window.innerWidth <= 960) {
                    closeSidebar();
                }

            });

        });

        window.addEventListener('resize', function () {

            if (window.innerWidth > 960) {
                closeSidebar();
            }

        });

    }


    /* ========================================================
       04. PASSWORD VISIBILITY TOGGLE
       Supports:
       data-toggle-password="password"
       data-toggle-password="confirm_password"
    ======================================================== */

    function initPasswordToggles() {

        $$('[data-toggle-password]').forEach(function (button) {

            button.addEventListener('click', function () {

                var inputId = button.getAttribute('data-toggle-password');
                var input = document.getElementById(inputId);

                if (!input) return;

                var icon = button.querySelector('i');
                var isPassword = input.type === 'password';

                input.type = isPassword ? 'text' : 'password';

                button.setAttribute(
                    'aria-label',
                    isPassword ? 'Hide password' : 'Show password'
                );

                if (icon) {

                    icon.classList.toggle('bi-eye', !isPassword);
                    icon.classList.toggle('bi-eye-slash', isPassword);

                }

            });

        });

    }


    /* ========================================================
       05. LIVE PASSWORD MATCH VALIDATION
       Registration page only
    ======================================================== */

    function initPasswordMatch() {

        var password = document.getElementById('password');
        var confirmPassword = document.getElementById('confirm_password');

        if (!password || !confirmPassword) return;

        function checkPasswordMatch() {

            if (!confirmPassword.value) {

                confirmPassword.setCustomValidity('');
                return;

            }

            if (password.value !== confirmPassword.value) {

                confirmPassword.setCustomValidity(
                    'Passwords do not match.'
                );

            } else {

                confirmPassword.setCustomValidity('');

            }

        }

        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);

        // Also check if the browser restores previously entered values.
        checkPasswordMatch();

    }


    /* ========================================================
       06. CONFIRMATION DIALOGS
    ======================================================== */

    function initConfirmations() {

        $$('[data-confirm]').forEach(function (element) {

            element.addEventListener('click', function (event) {

                var message = element.getAttribute('data-confirm');

                if (!message) {
                    message = 'Are you sure you want to continue?';
                }

                if (!window.confirm(message)) {

                    event.preventDefault();
                    event.stopPropagation();

                }

            });

        });

    }


    /* ========================================================
       07. AUTO-DISMISS ALERTS
    ======================================================== */

    function initFlashAutoDismiss() {

        $$('.alert[data-auto-dismiss], .alert').forEach(function (alert) {

            var delay = parseInt(
                alert.getAttribute('data-auto-dismiss') || '6000',
                10
            );

            window.setTimeout(function () {

                alert.style.transition =
                    'opacity .4s ease, transform .4s ease';

                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-8px)';

                window.setTimeout(function () {

                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }

                }, 450);

            }, delay);

        });

    }


    /* ========================================================
       08. IMAGE PREVIEW AND FILE SIZE VALIDATION
    ======================================================== */

    function initFilePreview() {

        $$('input[type="file"][data-preview]').forEach(function (input) {

            var previewId = input.getAttribute('data-preview');
            var previewBox = document.getElementById(previewId);

            var maxBytes = parseInt(
                input.getAttribute('data-max-bytes') || '0',
                10
            );

            if (!previewBox) return;

            input.addEventListener('change', function () {

                previewBox.replaceChildren();

                var file = input.files && input.files[0];

                if (!file) return;

                if (maxBytes > 0 && file.size > maxBytes) {

                    var error = document.createElement('p');

                    error.className = 'form-hint';
                    error.style.color = 'var(--danger)';

                    error.textContent =
                        'File is too large. Maximum size is ' +
                        (maxBytes / (1024 * 1024)).toFixed(1) +
                        ' MB.';

                    previewBox.appendChild(error);

                    input.value = '';

                    return;
                }

                if (!file.type || file.type.indexOf('image/') !== 0) {

                    var typeError = document.createElement('p');

                    typeError.className = 'form-hint';
                    typeError.style.color = 'var(--danger)';

                    typeError.textContent =
                        'Please select a valid image file.';

                    previewBox.appendChild(typeError);

                    input.value = '';

                    return;
                }

                var reader = new FileReader();

                reader.onload = function (event) {

                    var image = document.createElement('img');

                    image.src = event.target.result;
                    image.alt = 'Selected image preview';

                    image.style.maxWidth = '220px';
                    image.style.borderRadius = '10px';
                    image.style.border = '1px solid var(--border)';
                    image.style.marginTop = '10px';

                    previewBox.appendChild(image);

                };

                reader.onerror = function () {

                    var error = document.createElement('p');

                    error.textContent =
                        'Unable to preview the selected image.';

                    previewBox.appendChild(error);

                };

                reader.readAsDataURL(file);

            });

        });

    }


    /* ========================================================
       09. CHARACTER COUNTER
    ======================================================== */

    function initCharacterCounters() {

        $$('textarea[data-counter]').forEach(function (textarea) {

            var counterId = textarea.getAttribute('data-counter');
            var counter = document.getElementById(counterId);

            if (!counter) return;

            var maxLength = textarea.getAttribute('maxlength');

            function updateCounter() {

                var currentLength = textarea.value.length;

                if (maxLength) {

                    counter.textContent =
                        currentLength + ' / ' +
                        maxLength + ' characters';

                } else {

                    counter.textContent =
                        currentLength + ' characters';

                }

            }

            textarea.addEventListener('input', updateCounter);

            updateCounter();

        });

    }


    /* ========================================================
       10. LIVE SEARCH AND TABLE FILTER
    ======================================================== */

    function initLiveFilter() {

        $$('input[data-live-filter]').forEach(function (input) {

            var selector = input.getAttribute('data-live-filter');
            var target = document.querySelector(selector);

            if (!target) return;

            var timer = null;

            input.addEventListener('input', function () {

                window.clearTimeout(timer);

                timer = window.setTimeout(function () {

                    var keyword = input.value.trim().toLowerCase();

                    $$('tbody tr', target).forEach(function (row) {

                        var rowText = row.textContent.toLowerCase();

                        row.style.display =
                            (!keyword || rowText.indexOf(keyword) !== -1)
                                ? ''
                                : 'none';

                    });

                }, 180);

            });

        });

    }


    /* ========================================================
       11. PREVENT DOUBLE FORM SUBMISSION
    ======================================================== */

    function initDoubleSubmitGuard() {

        $$('form:not([data-no-double-submit])').forEach(function (form) {

            var submitting = false;

            form.addEventListener('submit', function (event) {

                if (submitting) {

                    event.preventDefault();
                    return;

                }

                var submitter = event.submitter;

                if (!submitter) {

                    submitter = form.querySelector(
                        'button[type="submit"], input[type="submit"]'
                    );

                }

                if (!submitter) return;

                submitting = true;

                var originalLabel =
                    submitter.tagName === 'INPUT'
                        ? submitter.value
                        : submitter.textContent;

                window.setTimeout(function () {

                    submitter.disabled = true;

                    submitter.dataset.originalLabel = originalLabel;

                    if (submitter.tagName === 'INPUT') {
                        submitter.value = 'Please wait...';
                    } else {
                        submitter.textContent = 'Please wait...';
                    }

                    form.setAttribute('aria-busy', 'true');

                }, 0);

            });

        });

    }


    /* ========================================================
       12. DROPDOWN HANDLER
    ======================================================== */

    function initOutsideClickClose() {

        $$('[data-dropdown]').forEach(function (trigger) {

            var menuId = trigger.getAttribute('data-dropdown');
            var menu = document.getElementById(menuId);

            if (!menu) return;

            function closeMenu() {

                menu.style.display = 'none';

                trigger.setAttribute('aria-expanded', 'false');

            }

            trigger.addEventListener('click', function (event) {

                event.stopPropagation();

                var isOpen = menu.style.display === 'block';

                closeMenu();

                if (!isOpen) {

                    menu.style.display = 'block';

                    trigger.setAttribute('aria-expanded', 'true');

                }

            });

            document.addEventListener('click', function (event) {

                if (
                    !menu.contains(event.target) &&
                    !trigger.contains(event.target)
                ) {
                    closeMenu();
                }

            });

            document.addEventListener('keydown', function (event) {

                if (event.key === 'Escape') {
                    closeMenu();
                }

            });

        });

    }


    /* ========================================================
       13. INITIALIZE ALL MODULES
    ======================================================== */

    function initializeLabSync() {

        initPublicNav();
        initSidebar();

        initPasswordToggles();
        initPasswordMatch();

        initConfirmations();
        initFlashAutoDismiss();
        initFilePreview();
        initCharacterCounters();
        initLiveFilter();
        initDoubleSubmitGuard();
        initOutsideClickClose();

        console.log('LabSync JavaScript initialized successfully.');

    }


    /* ========================================================
       14. START AFTER HTML LOADS
    ======================================================== */

    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            initializeLabSync
        );

    } else {

        initializeLabSync();

    }

})();
