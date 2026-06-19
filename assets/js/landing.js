(function () {
    const menuButton = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.main-nav');
    const authSwitches = document.querySelectorAll('[data-auth-switch]');
    const authPanels = document.querySelectorAll('[data-auth-panel]');
    const authNote = document.querySelector('[data-auth-note]');
    const authTitle = document.getElementById('loginTitle');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (menuButton && navigation) {
        menuButton.addEventListener('click', function () {
            const isOpen = navigation.classList.toggle('is-open');
            menuButton.setAttribute('aria-expanded', String(isOpen));
        });

        navigation.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navigation.classList.remove('is-open');
                menuButton.setAttribute('aria-expanded', 'false');
            });
        });
    }

    function selectAuthPanel(mode, shouldAnimate) {
        const loginPanel = document.querySelector('.login-panel');
        const startHeight = loginPanel ? loginPanel.offsetHeight : 0;

        authPanels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.authPanel === mode);
        });

        authSwitches.forEach(function (button) {
            const isActive = button.dataset.authSwitch === mode;

            if (button.getAttribute('role') === 'tab') {
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-selected', String(isActive));
            }
        });

        if (authNote) {
            authNote.innerHTML = mode === 'login'
                ? 'ยังไม่มีบัญชี? <button type="button" data-auth-switch="register">สมัครสมาชิก</button>'
                : 'มีบัญชีอยู่แล้ว? <button type="button" data-auth-switch="login">เข้าสู่ระบบ</button>';
        }

        if (authTitle) {
            authTitle.textContent = mode === 'login' ? 'เข้าสู่ระบบ' : 'สมัครสมาชิก';
        }

        if (shouldAnimate && !reducedMotion && loginPanel && typeof loginPanel.animate === 'function') {
            const endHeight = loginPanel.offsetHeight;

            if (startHeight !== endHeight) {
                loginPanel.style.overflow = 'hidden';
                const heightAnimation = loginPanel.animate(
                    [{ height: startHeight + 'px' }, { height: endHeight + 'px' }],
                    { duration: 380, easing: 'cubic-bezier(.2,.8,.2,1)' }
                );

                heightAnimation.addEventListener('finish', function () {
                    loginPanel.style.overflow = '';
                }, { once: true });
            }
        }
    }

    document.addEventListener('click', function (event) {
        const authSwitch = event.target.closest('[data-auth-switch]');

        if (authSwitch) {
            selectAuthPanel(authSwitch.dataset.authSwitch, true);
        }
    });

    document.querySelectorAll('.password-toggle').forEach(function (passwordButton) {
        passwordButton.addEventListener('click', function () {
            const passwordInput = passwordButton.closest('.input-wrap').querySelector('input');
            const shouldShow = passwordInput.type === 'password';
            passwordInput.type = shouldShow ? 'text' : 'password';
            passwordButton.setAttribute('aria-pressed', String(shouldShow));
            passwordButton.setAttribute('aria-label', shouldShow ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
        });
    });

    const initialPanel = document.querySelector('[data-auth-panel].is-active');
    selectAuthPanel(initialPanel ? initialPanel.dataset.authPanel : 'login', false);
}());
