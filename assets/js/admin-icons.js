/**
 * Icon-Auswahl (ACF-Select mit Wrapper-Klasse „zpt-icon-picker“): Vorschau des Icons in Liste und Auswahl.
 * Werte sind Kürzel wie „bi:arrow-right“, „fa-solid:house“, „mi-outlined:home“, „svg:logo“ (includes/icons.php).
 * Daten (aktive Familien, SVGs des eigenen Sets): zptIcons per wp_localize_script.
 */
(function ($) {
    if (typeof acf === 'undefined') {
        return;
    }
    const data = window.zptIcons || { families: {}, svg: {} };

    // Kürzel → Icon-Element (leer, wenn die Familie nicht geladen ist)
    function zptIconElement(token) {
        const [family, name] = String(token).includes(':') ? String(token).split(':', 2) : ['bi', String(token)];
        const def = data.families[family];
        if (!def || !name) {
            return $('<span>');
        }
        if (family === 'svg') {
            return $('<span>').html(data.svg[name] || '');
        }
        const $icon = $(def.ligature ? '<span>' : '<i>').addClass(def['class'].replace('%s', name));
        return def.ligature ? $icon.text(name) : $icon;
    }

    function zptIconTemplate(item) {
        if (!item.id) {
            return item.text;
        }
        const [name, label] = String(item.text).split(' · ');
        return $('<span class="zpt-icon-option">')
            .append(zptIconElement(item.id))
            .append($('<span>').text(name))
            .append(label ? $('<small>').text(label) : '');
    }

    acf.add_filter('select2_args', function (args, $select, settings, field) {
        if (field && field.$el && field.$el.hasClass('zpt-icon-picker')) {
            args.templateResult    = zptIconTemplate;
            args.templateSelection = zptIconTemplate;
        }
        return args;
    });
})(jQuery);
