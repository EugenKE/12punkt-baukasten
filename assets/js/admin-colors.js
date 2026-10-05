/**
 * Farbfelder (Textfeld mit Wrapper-Klasse „zpt-color-field“, siehe includes/colors.php):
 * Vorschau-Feld mit Farbwähler neben der Eingabe. Bei „zpt-color-tokens“ statt des Textfelds
 * zwei Auswahlen: Farbe (Baukasten-Farben, „eigene“ → Textfeld für Hex/Farbname) und
 * Shading (Shades aus den Einstellungen, z. B. 100 … 900). Gespeichert wird weiter ein Wert im Textfeld:
 * „primary“ (Grundfarbe), „primary-300“, „tomato“, „#ff6347“.
 *
 * Einstellungen → Farben:
 * - Tab „Einstellungen“ (Repeater zpt_shade_scale): Vorlage lädt ihre Shades in die Tabelle,
 *   Änderungen in der Tabelle stellen die Vorlage auf „Eigene“; die Zeile der Grundfarbe ist markiert.
 * - Farbpalette (Repeater „zpt-color-shades“, Felder shade_{name}): leere Shades zeigen den
 *   aus der Grundfarbe berechneten Wert als Vorschau und Platzhalter, wie ihn auch PHP ausgibt.
 * - Systemfarben (Repeater zpt_system_colors): Farbe wie die Props-Felder (Farbe + Shading), aber nur
 *   Palettenfarben („zpt-color-palette“, Stand des letzten Speicherns); Namensfeld schlägt die Bootstrap-Farben vor.
 * - Tab „WordPress“ (Repeater „zpt-wp-colors“): alle anderen Farbauswahlen zeigen nur diese Farben/Shades
 *   (zptColors.allowed; leer = alle). Buttons über der Liste: gesamte Farbpalette (mit Shades), nur Grundfarben, Systemfarben.
 */
(function ($) {
    if (typeof acf === 'undefined') {
        return;
    }
    var __ = wp.i18n.__;

    var tokens = (window.zptColors && zptColors.tokens) || {};
    var scale  = (window.zptColors && zptColors.scale) || [];
    var presets = (window.zptColors && zptColors.presets) || {};
    var bootstrapNames = (window.zptColors && zptColors.bootstrap) || [];

    // Shade-Name → {type, amount} (gespeicherte Skala)
    var shades = {};
    scale.forEach(function (s) {
        shades[s.name] = s;
    });
    var probe  = null;

    // --- CSS-Farbe → [r, g, b] (über ein unsichtbares Element)
    var zptColorRgb = function (color) {
        if (!probe) {
            probe = document.createElement('span');
            probe.style.display = 'none';
            document.body.appendChild(probe);
        }
        probe.style.color = '';
        probe.style.color = color;
        var m = probe.style.color ? getComputedStyle(probe).color.match(/\d+(\.\d+)?/g) : null;
        return m ? m.slice(0, 3).map(Number) : null;
    };

    // --- berechneter Shade wie zpt_color_shade() in PHP
    var zptColorShade = function (rgb, shade) {
        var mix = shades[shade];
        if (!rgb || !mix) {
            return '';
        }
        return '#' + rgb.map(function (c) {
            var v = mix.type === 'tint' ? c + (255 - c) * mix.amount : (mix.type === 'shade' ? c * (1 - mix.amount) : c);
            return ('0' + Math.round(v).toString(16)).slice(-2);
        }).join('');
    };

    // --- Token → [Farbe, Shade] („primary-300“ → ['primary', '300']), sonst null
    var zptColorTokenSplit = function (token) {
        for (var i = 0; i < scale.length; i++) {
            var suffix = '-' + scale[i].name;
            var color  = token.slice(0, -suffix.length);
            if (token.length > suffix.length && token.slice(-suffix.length) === suffix && tokens[color] !== undefined) {
                return [color, scale[i].name];
            }
        }
        return null;
    };

    // --- Baukasten-Farben mit ihren Shades: {primary: ['100', …, '900'], …}
    var zptColorNames = function () {
        var names = {};
        Object.keys(tokens).forEach(function (token) {
            var parts = zptColorTokenSplit(token);
            if (parts) {
                (names[parts[0]] = names[parts[0]] || []).push(parts[1]);
            } else {
                names[token] = names[token] || [];
            }
        });
        return names;
    };

    // --- gespeicherter Wert → {color, shade} | {custom: true}
    var zptColorSplit = function (value, names) {
        value = (value || '').trim().toLowerCase();
        if (value === '') {
            return {color: '', shade: ''};
        }
        var parts = tokens[value] !== undefined ? zptColorTokenSplit(value) : null;
        if (parts && names[parts[0]] && names[parts[0]].indexOf(parts[1]) !== -1) {
            return {color: parts[0], shade: parts[1]};
        }
        if (names[value]) {
            return {color: value, shade: String(zptColors.base)};
        }
        return {custom: true};
    };

    // --- Eingabe → CSS-Farbe ('' = ungültig)
    var zptColorResolve = function (value, withTokens) {
        value = (value || '').trim().toLowerCase();
        if (!value) {
            return '';
        }
        if (withTokens && tokens[value]) {
            return tokens[value];
        }
        // nur Farbnamen und Hexwerte (wie in PHP), keine rgb()/hsl()
        if (/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/.test(value) || (/^[a-z]+$/.test(value) && CSS.supports('color', value))) {
            return value;
        }
        return '';
    };

    // --- berechnete Farbe → #rrggbb für den Farbwähler
    var zptColorToHex = function (el) {
        var m = getComputedStyle(el).backgroundColor.match(/\d+(\.\d+)?/g);
        if (!m || (m[3] !== undefined && +m[3] === 0)) {
            return '#ffffff';
        }
        return '#' + m.slice(0, 3).map(function (c) {
            return ('0' + Math.round(+c).toString(16)).slice(-2);
        }).join('');
    };

    // --- Farbe + Shading als Auswahlen vor dem (versteckten) Textfeld
    var zptColorSelects = function ($field, $input) {
        var names  = zptColorNames();
        // „zpt-color-palette“ (Systemfarben): nur Palettenfarben, keine anderen Systemfarben
        if ($field.hasClass('zpt-color-palette')) {
            var palette = (window.zptColors && zptColors.palette) || [];
            Object.keys(names).forEach(function (name) {
                if (palette.indexOf(name) === -1) {
                    delete names[name];
                }
            });
        }
        // sonst nur die Farben aus Tab „WordPress“ (leer = alle; die Liste dort selbst zeigt alle)
        var allowed = (window.zptColors && zptColors.allowed) || [];
        if (allowed.length && !$field.hasClass('zpt-color-palette') && !$field.closest('.zpt-wp-colors').length) {
            var baseShade = String(zptColors.base);
            Object.keys(names).forEach(function (name) {
                names[name] = names[name].filter(function (shade) {
                    return allowed.indexOf(name + '-' + shade) !== -1 || (shade === baseShade && allowed.indexOf(name) !== -1);
                });
                if (!names[name].length && allowed.indexOf(name) === -1) {
                    delete names[name];
                }
            });
        }
        var base   = String(zptColors.base);
        var $color = $('<select class="zpt-color-name"></select>').attr('aria-label', __('Farbe', '12punkt-baukasten'));
        var $shade = $('<select class="zpt-color-shade"></select>').attr('aria-label', __('Shading', '12punkt-baukasten'));

        $color.append(new Option($input.attr('placeholder') || '–', ''));
        Object.keys(names).forEach(function (name) {
            $color.append(new Option(name, name));
        });
        $color.append(new Option(__('eigene', '12punkt-baukasten'), '_custom'));
        $input.before($color, $shade);
        // Textfeld ist nur noch für „eigene“ sichtbar
        $input.attr('placeholder', '#ff6347 / tomato');

        var fillShades = function (color, shade) {
            $shade.empty();
            (names[color] || []).forEach(function (s) {
                $shade.append(new Option(s, s));
            });
            $shade.val(shade || base).prop('disabled', !color);
        };

        // Textfeld → Auswahlen
        var fromInput = function () {
            var parts = zptColorSplit($input.val(), names);
            $field.toggleClass('-custom', !!parts.custom);
            $color.val(parts.custom ? '_custom' : parts.color);
            fillShades(parts.custom ? '' : parts.color, parts.shade);
        };

        // Auswahlen → Textfeld
        var toInput = function () {
            var color = $color.val();
            if (color === '_custom') {
                $field.addClass('-custom');
                fillShades('', '');
                $input.trigger('focus');
                return;
            }
            $field.removeClass('-custom');
            if ($shade.prop('disabled') || !names[color] || names[color].indexOf($shade.val()) === -1) {
                fillShades(color, base);
            }
            var shade = $shade.val();
            $input.val(!color ? '' : (shade === base || !shade ? color : color + '-' + shade)).trigger('change');
        };

        $color.add($shade).on('change', toInput);
        $input.on('change', function () {
            if (!$field.hasClass('-custom') || $color.val() !== '_custom') {
                fromInput();
            }
        });
        $field.addClass('-tokens');
        fromInput();
    };

    acf.addAction('new_field/type=text', function (field) {
        var $field = field.$el;
        if (!$field.hasClass('zpt-color-field') || $field.data('zptColor')) {
            return;
        }
        $field.data('zptColor', true);

        var withTokens = $field.hasClass('zpt-color-tokens');
        var $input     = field.$input();
        var $swatch    = $('<label class="zpt-color-swatch"><span></span><input type="color" tabindex="-1"></label>').attr('title', __('Farbe wählen', '12punkt-baukasten'));
        var $preview   = $swatch.children('span');
        var $picker    = $swatch.children('input');

        $input.after($swatch);
        if (withTokens) {
            zptColorSelects($field, $input);
        }

        // Shade-Zeile in Einstellungen → Farben (Zeile der Farbpalette): Grundfarbe und Stufe dieses Felds
        var $group = $field.closest('.zpt-color-shades .acf-row, .zpt-color-shades');
        var shade  = String($field.data('name')).replace(/^shade_/, '');
        var isBase = $field.hasClass('zpt-color-base');
        var $base  = $group.length && !isBase ? $group.find('.zpt-color-base input[type=text]').first() : $();

        var update = function () {
            var value = $input.val().trim();
            var color = zptColorResolve(value, withTokens);
            var auto  = '';
            if (value === '' && $base.length) {
                auto = zptColorShade(zptColorRgb(zptColorResolve($base.val(), false)), shade);
                $input.attr('placeholder', auto || __('automatisch', '12punkt-baukasten'));
            }
            $preview.css('background', color || auto || 'transparent');
            $field.toggleClass('-invalid', value !== '' && color === '');
            $field.toggleClass('-auto', auto !== '');
            $picker.val(color || auto ? zptColorToHex($preview[0]) : '#ffffff');
        };

        $input.on('input change', update);
        if ($base.length) {
            $base.on('input change', update);
        }
        $picker.on('input', function () {
            $input.val(this.value).trigger('change');
        });
        update();
    });

    // ------------------------------------------------------------- SYSTEMFARBEN

    // --- Namensfeld der Systemfarben: Bootstrap-Namen vorschlagen
    var zptSystemNamesList = function () {
        if (!document.getElementById('zpt-system-color-names')) {
            var list = document.createElement('datalist');
            list.id = 'zpt-system-color-names';
            bootstrapNames.forEach(function (name) {
                list.appendChild(new Option(name, name));
            });
            document.body.appendChild(list);
        }
        $('.zpt-system-colors [data-name="name"] input[type=text]').attr('list', 'zpt-system-color-names');
    };
    acf.addAction('ready', zptSystemNamesList);
    acf.addAction('append', zptSystemNamesList);

    // ------------------------------------------------------------- TAB „WORDPRESS“

    // --- Buttons über der Liste (fehlende Einträge anhängen):
    //     Palette mit allen Shades / nur Grundfarben der Palette / Systemfarben (Grundfarbe)
    acf.addAction('ready_field/key=field_zpt_settings_wp_colors', function (field) {
        var lists = (window.zptColors && zptColors.wpLists) || {palette: [], base: [], system: []};
        var $bar = $('<p class="zpt-wp-colors-actions"></p>')
            .append($('<a href="#" class="button" data-list="palette">').text(__('Gesamte Farbpalette übernehmen', '12punkt-baukasten')), ' ')
            .append($('<a href="#" class="button" data-list="base">').text(__('nur Grundfarben übernehmen', '12punkt-baukasten')), ' ')
            .append($('<a href="#" class="button" data-list="system">').text(__('Systemfarben übernehmen', '12punkt-baukasten')));
        field.$control().before($bar);

        $bar.on('click', 'a', function (e) {
            e.preventDefault();
            var present = field.$rows().map(function () {
                return ($(this).find('[data-name="color"] input[type=text]').val() || '').trim().toLowerCase();
            }).get();
            lists[$(this).data('list')].forEach(function (color) {
                if (present.indexOf(color) !== -1) {
                    return;
                }
                present.push(color);
                field.add();
                field.$rows().last().find('[data-name="color"] input[type=text]').val(color).trigger('change');
            });
        });
    });

    // ------------------------------------------------------------- SHADE-SKALA (TAB „EINSTELLUNGEN“)

    var zptScaleFilling = false;

    // --- Zeile der Grundfarbe markieren
    var zptScaleMark = function () {
        $('.zpt-shade-scale .acf-row').not('.acf-clone').each(function () {
            $(this).toggleClass('-base', $(this).find('[data-name="type"] select').val() === 'base');
        });
    };

    // --- Vorlage in die Tabelle laden
    var zptScaleLoad = function (preset) {
        var field = acf.getField('field_zpt_settings_shade_scale');
        if (!field || !presets[preset]) {
            return;
        }
        zptScaleFilling = true;
        field.$rows().remove();
        presets[preset].scale.forEach(function (shade) {
            field.add();
            var $row = field.$rows().last();
            $row.find('[data-name="name"] input').val(shade[0]);
            $row.find('[data-name="type"] select').val(shade[1]).trigger('change');
            $row.find('[data-name="amount"] input').val(shade[1] === 'base' ? '' : shade[2]);
        });
        field.render();
        zptScaleFilling = false;
        zptScaleMark();
    };

    $(document).on('change', '.acf-field[data-name="zpt_shade_preset"] select', function () {
        zptScaleLoad(this.value);
    });

    // Änderung in der Tabelle → Vorlage „Eigene“
    $(document).on('input change', '.zpt-shade-scale input, .zpt-shade-scale select', function () {
        zptScaleMark();
        if (!zptScaleFilling) {
            $('.acf-field[data-name="zpt_shade_preset"] select').val('custom');
        }
    });
    acf.addAction('append', function ($el) {
        if (!zptScaleFilling && $el.closest('.zpt-shade-scale').length) {
            $('.acf-field[data-name="zpt_shade_preset"] select').val('custom');
        }
    });
    acf.addAction('ready', zptScaleMark);
})(jQuery);
