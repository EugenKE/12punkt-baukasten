/**
 * Baukasten → Elemente.
 * - Akkordeon-Titel: Zusammenfassung der gesetzten Werte („Sans-Serif · 700 · Größe XS xl, MD xxl · Margin“)
 * - Gewicht: nur Gewichte der Schrift der gewählten Rolle (leer = Grundschrift), Gewichte je Rolle: zptRoleWeights
 * - Matrix-Zeilen: leere Breakpoints zeigen den geerbten Wert („← 20“, kurz, damit er ins Feld passt)
 * - Felder eines Elements erst beim Aufklappen laden (Platzhalter .zpt-element.-lazy, siehe elements.php);
 *   bis dahin kommt die Zusammenfassung aus PHP (zptElements.summaries)
 */
(function ($) {
    if (typeof acf === 'undefined') {
        return;
    }
    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf;

    var BREAKPOINTS = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];

    // Beim Laden alle Akkordeons zu: ACF merkt sich offene Akkordeons je Seite („this.accordions“,
    // Schlüssel mit acf.data.post_id) und öffnet sie wieder – Erinnerung löschen, bevor ACF die Felder
    // einrichtet. Erst bei „prepare“: vorher ist acf.data (post_id) noch nicht gesetzt.
    acf.addAction('prepare', function () {
        acf.setPreference('this.accordions', null);
    }, 1);

    // ------------------------------------------------------------- Zusammenfassung

    function zptFieldValue($group, name) {
        var $field = $group.find('.acf-field[data-name="' + name + '"]').first();
        // Farbfelder: gespeicherter Wert steht im (versteckten) Textfeld, nicht in den Auswahlen davor
        var $text = $field.find('input[type=text]');
        return ($text.length ? $text : $field.find('select, input[type=number]')).first().val() || '';
    }

    function zptOptionText($group, name) {
        var $select = $group.find('.acf-field[data-name="' + name + '"] select').first();
        return $select.val() ? $select.find('option:selected').text() : '';
    }

    // ------------------------------------------------------------- Matrix-Zeilen

    function zptCell($row, bp) {
        return $row.find('> .acf-input > .acf-table > tbody > tr > td.acf-field[data-name="' + bp + '"]');
    }

    function zptCellInput($row, bp) {
        return zptCell($row, bp).find('select, input').first();
    }

    /** Text eines Werts: Auswahl → Label der Option, Zahl → Zahl */
    function zptValueText($input) {
        return $input.is('select') ? $input.find('option:selected').text() : $input.val();
    }

    /** Leere Felder beschriften: XS „–“, ab SM „← geerbter Wert“ bzw. „wie kleiner“, solange links nichts gesetzt ist */
    function zptSetEmptyText($input, text) {
        if ($input.is('select')) {
            $input.find('option[value=""]').text(text);
        } else {
            $input.attr('placeholder', text);
        }
    }

    function zptUpdateRow($row) {
        var inherited = '';

        BREAKPOINTS.forEach(function (bp) {
            var $input = zptCellInput($row, bp);

            if (bp === 'xs') {
                zptSetEmptyText($input, '–');
            } else {
                zptSetEmptyText($input, inherited ? '← ' + inherited : 'wie kleiner');
            }
            if ($input.val() !== '' && $input.val() !== null) {
                inherited = zptValueText($input);
            }
        });
    }

    /** Matrix-Zeile mit gesetzten Werten? → ['XS xl', 'MD xxl'] */
    function zptMatrixValues($group, name) {
        var $row = $group.find('.acf-field.zpt-bp-row[data-name="' + name + '"]').first();
        var values = [];
        BREAKPOINTS.forEach(function (bp) {
            var $input = zptCellInput($row, bp);
            if ($input.val()) {
                values.push(bp.toUpperCase() + ' ' + zptValueText($input));
            }
        });
        return values;
    }

    function zptHasSpacing($group, prop) {
        return ['top', 'right', 'bottom', 'left'].some(function (side) {
            return zptMatrixValues($group, prop + '_' + side).length > 0;
        });
    }

    function zptSummary($group) {
        var parts = [];
        // Logo-Größe (nur Logo / Name): Art als Button-Gruppe, Werte als Matrix-Zeile
        var logoMode = $group.find('.acf-field[data-name="logo_size_mode"] input:checked').val();
        if (logoMode === 'nav') {
            parts.push(__('Logo: Höhe der Navigation', '12punkt-baukasten'));
        } else if (logoMode) {
            var logo = zptMatrixValues($group, 'logo_size').map(function (v) {
                return v.replace(/^XS /, '');
            });
            if (logo.length) {
                parts.push(sprintf(/* translators: %s: Werte je Breakpoint */ logoMode === 'width' ? __('Logo-Breite %s', '12punkt-baukasten') : __('Logo-Höhe %s', '12punkt-baukasten'), logo.join(', ')));
            }
        }
        var role = zptOptionText($group, 'role');
        var weight = zptFieldValue($group, 'font_weight');
        var color = zptFieldValue($group, 'color');
        var hover = zptFieldValue($group, 'color_hover');
        var list = zptOptionText($group, 'list_style');
        var options = $group.find('.acf-field[data-name="options"] input:checked').map(function () {
            return $(this).parent().text().trim();
        }).get();
        var size = zptMatrixValues($group, 'size').map(function (v) {
            return v.replace(/^XS /, '');
        });

        if (role) parts.push(role);
        if (weight) parts.push(weight);
        if (options.length) parts.push(options.join(', '));
        if (color) parts.push(color + (hover ? ' / ' + hover : ''));
        else if (hover) parts.push(sprintf(/* translators: %s: Farbe */ __('Hover %s', '12punkt-baukasten'), hover));
        var background = zptFieldValue($group, 'background');
        if (background) parts.push(sprintf(/* translators: %s: Farbe */ __('Hintergrund %s', '12punkt-baukasten'), background));
        if (list) parts.push(list);
        if (size.length) parts.push(sprintf(/* translators: %s: Schriftgrößen je Breakpoint */ __('Größe %s', '12punkt-baukasten'), size.join(', ')));
        if (zptHasSpacing($group, 'margin')) parts.push(__('Margin', '12punkt-baukasten'));
        if (zptHasSpacing($group, 'padding')) parts.push(__('Padding', '12punkt-baukasten'));

        return parts.join(' · ');
    }

    function zptUpdateSummary($group) {
        var $title = $group.closest('.acf-field-accordion').find('> .acf-accordion-title').first();
        var $summary = $title.find('.zpt-el-summary');
        if (!$summary.length) {
            $summary = $('<span class="zpt-el-summary"></span>').insertAfter($title.find('label').first());
        }
        var text = zptSummary($group);
        $summary.text(text || __('– nicht gesetzt –', '12punkt-baukasten'));
    }

    // ------------------------------------------------------------- Gewicht je Rolle

    function zptRoleAllowed(role) {
        role = role || 'base';
        return typeof zptRoleWeights !== 'undefined' && Object.prototype.hasOwnProperty.call(zptRoleWeights, role)
            ? zptRoleWeights[role]
            : null; // unbekannt → keine Einschränkung
    }

    /**
     * resetInvalid: Auswahl auf „Standard“ setzen, wenn das Gewicht in der Schrift fehlt
     * (nur bei Rollenwechsel, nicht beim Laden).
     */
    function zptFilterWeights($group, resetInvalid) {
        var $weight = $group.find('.acf-field[data-name="font_weight"] select').first();
        if (!$weight.length) {
            return;
        }
        var allowed = zptRoleAllowed(zptFieldValue($group, 'role'));
        var current = $weight.val();

        $weight.find('option').each(function () {
            var keep = !this.value || !allowed || allowed.indexOf(this.value) !== -1 || (!resetInvalid && this.value === current);
            this.hidden = !keep;
            this.disabled = !keep;
        });

        if (resetInvalid && current && $weight.find('option:selected').prop('disabled')) {
            $weight.val('').trigger('change');
        }
    }

    // ------------------------------------------------------------- Init

    var GROUP = '.acf-field.zpt-element';

    function zptElementName($group) {
        return String($group.data('key') || '').replace(/^field_zpt_element_/, '');
    }

    /** Geladene Felder einrichten: Gewichte filtern, „wie kleiner“, Zusammenfassung */
    function zptInitGroup($group) {
        zptFilterWeights($group, false);
        $group.find('.acf-field.zpt-bp-row').each(function () {
            zptUpdateRow($(this));
        });
        zptUpdateSummary($group);
    }

    /** Zusammenfassung eines noch nicht geladenen Elements (aus PHP) */
    function zptLazySummary($group) {
        var summaries = (window.zptElements && zptElements.summaries) || {};
        var $title = $group.closest('.acf-field-accordion').find('> .acf-accordion-title').first();
        var $summary = $title.find('.zpt-el-summary');
        if (!$summary.length) {
            $summary = $('<span class="zpt-el-summary"></span>').insertAfter($title.find('label').first());
        }
        $summary.text(summaries[zptElementName($group)] || __('– nicht gesetzt –', '12punkt-baukasten'));
    }

    /** Felder eines Elements per AJAX holen, Platzhalter ersetzen, bei ACF anmelden */
    function zptLoadGroup($group) {
        if (!$group.hasClass('-lazy') || $group.data('zptLoading')) {
            return;
        }
        $group.data('zptLoading', true);

        var $input = $group.children('.acf-input');
        $input.html('<p class="zpt-el-loading"><span class="acf-loading"></span> ' + __('Lädt …', '12punkt-baukasten') + '</p>');

        $.post(zptElements.ajax, {
            action: 'zpt_element_fields',
            _ajax_nonce: zptElements.nonce,
            element: zptElementName($group)
        }).done(function (response) {
            var $new = response && response.success ? $(response.data.html).filter(GROUP).first() : $();
            if (!$new.length) {
                return zptLoadFailed($group, $input);
            }
            var $parent = $group.parent();
            $group.replaceWith($new);
            acf.doAction('append', $parent); // ACF richtet die neuen Felder ein (wie bei Repeater-Zeilen)
            zptInitGroup($new);
        }).fail(function () {
            zptLoadFailed($group, $input);
        });
    }

    function zptLoadFailed($group, $input) {
        $group.data('zptLoading', false);
        $input.html($('<p class="zpt-el-loading -error">').text(__('Felder konnten nicht geladen werden – Akkordeon erneut öffnen oder Seite neu laden.', '12punkt-baukasten')));
    }

    acf.addAction('ready', function () {
        $(GROUP).each(function () {
            var $group = $(this);
            if ($group.hasClass('-lazy')) {
                zptLazySummary($group);
                // von ACF als geöffnet gemerkte Akkordeons gleich laden
                if ($group.closest('.acf-field-accordion').hasClass('-open')) {
                    zptLoadGroup($group);
                }
            } else {
                zptInitGroup($group);
            }
        });
    });

    // Aufklappen → laden
    $(document).on('click', '.zpt-element-accordion > .acf-accordion-title', function () {
        zptLoadGroup($(this).closest('.acf-field-accordion').find(GROUP + '.-lazy').first());
    });

    $(document).on('change input', GROUP + ' select, ' + GROUP + ' input', function () {
        var $group = $(this).closest(GROUP);
        var $row = $(this).closest('.acf-field.zpt-bp-row');
        if ($row.length) {
            zptUpdateRow($row);
        }
        if ($(this).closest('.acf-field').data('name') === 'role') {
            zptFilterWeights($group, true);
        }
        zptUpdateSummary($group);
    });
})(jQuery);
