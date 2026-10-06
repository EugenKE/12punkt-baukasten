/**
 * 12punkt Baukasten – Sektion bearbeiten: Hintergrund (Box „Hintergrund“, acf-json/group_zpt_section_bg.json)
 * live im Editor zeigen. Im Frontend liegt er am <section> (zpt_background_style()), das es im Editor nicht
 * gibt – hier daher am .editor-styles-wrapper (im iframe des Editors, dort gelten auch die --theme-*-Variablen).
 */
(function ($) {
    if (typeof acf === 'undefined' || !window.wp || !wp.data) {
        return;
    }

    var BOX = '#acf-group_zpt_section_bg';
    var mediaUrls = {};

    function field(name) {
        return $(BOX + ' .acf-field[data-name="' + name + '"]');
    }

    /** Wert eines Farbfelds → CSS: Baukasten-Farbe als Variable, sonst wie eingegeben (ungültig ignoriert der Browser) */
    function colorCss(value) {
        value = String(value || '').trim().toLowerCase();
        var tokens = (window.zptColors && zptColors.tokens) || {};
        return Object.prototype.hasOwnProperty.call(tokens, value) ? 'var(--theme-' + value + ')' : value;
    }

    /** Bild-URL: Theme-Bild aus zptThemeImages, Mediathek per wp.data (einmal geladen, dann gemerkt) */
    function imageUrl(done) {
        if (field('zpt_bg_image_source').find('input:checked').val() === 'theme') {
            var rel = field('zpt_bg_theme_image').find('select').val();
            return done((rel && window.zptThemeImages && zptThemeImages[rel]) || '');
        }
        var id = parseInt(field('zpt_bg_image').find('input[type="hidden"]').val(), 10);
        if (!id) {
            return done('');
        }
        if (mediaUrls[id] !== undefined) {
            return done(mediaUrls[id]);
        }
        wp.data.resolveSelect('core').getMedia(id, {context: 'view'}).then(function (media) {
            mediaUrls[id] = (media && media.source_url) || '';
            done(mediaUrls[id]);
        }, function () {
            done('');
        });
    }

    /** .editor-styles-wrapper im iframe des Editors (oder ohne iframe im Dokument) */
    function wrapper() {
        var iframe = document.querySelector('iframe[name="editor-canvas"]');
        var doc = iframe && iframe.contentDocument ? iframe.contentDocument : document;
        return doc.querySelector('.editor-styles-wrapper');
    }

    function apply() {
        imageUrl(function (url) {
            var el = wrapper();
            if (!el) {
                return;
            }
            var repeat = field('zpt_bg_repeat').find('select').val() || 'no-repeat';
            var size = parseFloat(field('zpt_bg_size').find('input').val());

            el.style.backgroundColor = colorCss(field('zpt_bg_color').find('input[type="text"]').val());
            el.style.backgroundImage = url ? 'url("' + url.replace(/"/g, '\\"') + '")' : '';
            // wie im Frontend: ohne Wiederholen füllend, mit Wiederholen Originalgröße, Größe in % der Breite
            el.style.backgroundRepeat = url ? repeat : '';
            el.style.backgroundSize = !url ? '' : size > 0 ? size + '% auto' : repeat === 'no-repeat' ? 'cover' : 'auto';
            el.style.backgroundPosition = url ? 'center' : '';
        });
    }

    // Delegiert binden: im Block-Editor hängt React die Meta-Box erst nach „ready“ ein
    // (auch das versteckte Feld des Bildfelds – Auswahl/Entfernen löst dort change aus)
    $(document).on('change input', BOX + ' input, ' + BOX + ' select', apply);

    // Editor (iframe) und Box erscheinen später bzw. neu, z. B. nach Wechsel Code-/Visuell-Ansicht
    var last = null;
    wp.data.subscribe(function () {
        var el = wrapper();
        if (el && el !== last && $(BOX).length) {
            last = el;
            apply();
        }
    });
})(jQuery);
