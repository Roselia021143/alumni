(function () {
    const menuButton = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.main-nav');
    const passwordButton = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('landingPassword');

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

    if (passwordButton && passwordInput) {
        passwordButton.addEventListener('click', function () {
            const shouldShow = passwordInput.type === 'password';
            passwordInput.type = shouldShow ? 'text' : 'password';
            passwordButton.setAttribute('aria-pressed', String(shouldShow));
            passwordButton.setAttribute('aria-label', shouldShow ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
        });
    }
}());
