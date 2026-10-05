/**
 * 12punkt Baukasten – Sprungmarken
 * - Angeheftete Rows (.zpt-row-sticky) und sticky Header (.zpt-sticky-header, Baukasten → Navigation):
 *   Rows kleben unter dem Header (top), scroll-padding-top = Gesamthöhe, damit Sprungziele nicht darunter verschwinden.
 * - Navigation mit [data-zpt-scrollspy] und die Navigationsleiste (.zpt-navbar): Link zum gerade sichtbaren
 *   Abschnitt bekommt .active (Links „#id“ oder „/seite/#id“ auf diese Seite);
 *   bei seitlich wischbarer Navigation wird der aktive Link in den sichtbaren Bereich geschoben.
 */
(function () {
    var root = document.documentElement;
    var navs = [];

    // Angeheftete Elemente stapeln sich: jede Row klebt unter dem, was davor angeheftet ist (Header, andere Rows)
    function stickyOffset() {
        var height = 0;
        document.querySelectorAll('.zpt-row-sticky, .zpt-sticky-header').forEach(function (el) {
            if (el.classList.contains('zpt-row-sticky')) {
                el.style.top = height + 'px';
            }
            height += el.offsetHeight;
        });
        root.style.scrollPaddingTop = height ? height + 'px' : '';
        return height;
    }

    function setActive(nav, active) {
        nav.links.forEach(function (link) {
            var on = link === active;
            link.classList.toggle('active', on);
            if (on) {
                link.setAttribute('aria-current', 'true');
            } else {
                link.removeAttribute('aria-current');
            }
        });
        if (active && active !== nav.active && nav.el.scrollWidth > nav.el.clientWidth) {
            var navRect = nav.el.getBoundingClientRect();
            var linkRect = active.getBoundingClientRect();
            nav.el.scrollTo({
                left: nav.el.scrollLeft + linkRect.left - navRect.left - (navRect.width - linkRect.width) / 2,
                behavior: 'smooth'
            });
        }
        nav.active = active;
    }

    function update() {
        var offset = stickyOffset() + 20;
        var atBottom = window.innerHeight + window.scrollY >= root.scrollHeight - 2;
        navs.forEach(function (nav) {
            var active = null;
            nav.links.forEach(function (link) {
                var target = document.getElementById(decodeURIComponent(link.hash.slice(1)));
                if (target && target.getBoundingClientRect().top <= offset) {
                    active = link;
                }
            });
            if (atBottom && nav.links.length) {
                active = nav.links[nav.links.length - 1];
            }
            setActive(nav, active);
        });
    }

    function init() {
        document.querySelectorAll('[data-zpt-scrollspy], .zpt-navbar').forEach(function (el) {
            // Sprungmarken auf diese Seite (auch absolute Links „/#id“), Ziel muss existieren
            var links = Array.prototype.filter.call(el.querySelectorAll('a[href*="#"]'), function (link) {
                return link.hash.length > 1 && link.host === location.host && link.pathname === location.pathname
                    && document.getElementById(decodeURIComponent(link.hash.slice(1)));
            });
            if (links.length) {
                navs.push({ el: el, links: links, active: null });
            }
        });

        var ticking = false;
        function onScroll() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(function () {
                    ticking = false;
                    update();
                });
            }
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
        update();

        // Aufruf mit #anker (von einer anderen Seite): der Browser ist schon gesprungen, bevor der Abstand
        // unter dem sticky Header stand – mit Abstand neu anspringen
        var target = location.hash.length > 1 && document.getElementById(decodeURIComponent(location.hash.slice(1)));
        if (target && stickyOffset()) {
            target.scrollIntoView();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
