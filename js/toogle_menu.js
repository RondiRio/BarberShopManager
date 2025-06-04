document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const navList = document.getElementById('nav-list');

    if (menuToggle && navList) { // Garante que os elementos existam
        menuToggle.addEventListener('click', function() {
            const isShown = navList.classList.toggle('show');
            menuToggle.classList.toggle('active', isShown); 
            menuToggle.setAttribute('aria-expanded', isShown);
        });

        // Opcional: Fecha o menu ao clicar em um link (bom para navegação na mesma página ou UX mobile)
        navList.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768 && navList.classList.contains('show')) {
                    navList.classList.remove('show');
                    menuToggle.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Opcional: Fecha o menu se clicar fora dele
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navList.contains(event.target);
            const isClickOnToggle = menuToggle.contains(event.target);

            if (!isClickInsideNav && !isClickOnToggle && navList.classList.contains('show')) {
                navList.classList.remove('show');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
});