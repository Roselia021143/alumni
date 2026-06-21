(function () {
    const toggle = document.querySelector('.student-menu-toggle');
    const navigation = document.getElementById('studentNavigation');

    if (!toggle || !navigation) {
        return;
    }

    function closeMenu() {
        navigation.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'เปิดเมนู');
    }

    toggle.addEventListener('click', function () {
        const isOpen = navigation.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'ปิดเมนู' : 'เปิดเมนู');
    });

    navigation.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
            toggle.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 760) {
            closeMenu();
        }
    });
}());
