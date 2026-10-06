/**
 * 12punkt Baukasten – Logo so hoch wie die Navigation (Baukasten → Logo & Bilder → Größe: „Höhe der Navigation“)
 * Misst die Menüpunkte (.navbar-nav), bei eingeklapptem Menü den Menü-Button, und setzt --zpt-logo-height
 * an der Leiste (abzüglich Padding oben/unten von .navbar-brand). CSS: zpt_logo_css() in includes/logo.php.
 */
(function () {
    document.querySelectorAll('.zpt-navbar.zpt-logo-fit').forEach(function (navbar) {
        var brand = navbar.querySelector('.navbar-brand');
        var toggler = navbar.querySelector('.navbar-toggler');
        var nav = navbar.querySelector('.navbar-nav');
        if (!brand || (!toggler && !nav)) {
            return;
        }

        function visible(el) {
            return el && getComputedStyle(el).display !== 'none';
        }

        function update() {
            // Menü-Button sichtbar = Menü eingeklappt (mobil): Höhe des Buttons, sonst der Menüpunkte
            var ref = visible(toggler) ? toggler : nav;
            if (!visible(ref)) {
                return;
            }
            var style = getComputedStyle(brand);
            var height = ref.offsetHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom);
            if (height > 0) {
                navbar.style.setProperty('--zpt-logo-height', height + 'px');
            }
        }

        update();
        if (window.ResizeObserver) {
            var observer = new ResizeObserver(update);
            [toggler, nav].forEach(function (el) {
                if (el) {
                    observer.observe(el);
                }
            });
        }
        window.addEventListener('resize', update);
        // Webfonts ändern die Höhe der Menüpunkte
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(update);
        }
    });
})();
