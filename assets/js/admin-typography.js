/**
 * Baukasten → Typografie, Box „Schriftschnitte & Maße“: Repeater als Akkordeon.
 * Beim Laden sind alle Schriften zugeklappt, es ist immer nur eine offen.
 * Öffnen: Klick auf die zugeklappte Zeile (außer auf die Auswahl) oder auf das Pfeil-Icon links.
 */
(function ($) {
    if (typeof acf === 'undefined') {
        return;
    }

    acf.addAction('ready_field/key=field_zpt_fonts', function (field) {
        var ownRow = function ($row) {
            return $row.is('.acf-row') && !$row.is('.acf-clone') && $row.closest('.acf-field')[0] === field.$el[0];
        };
        var closeOthers = function ($open) {
            field.$rows().not($open).each(function () {
                var $row = $(this);
                if (!field.isCollapsed($row)) {
                    field.collapse($row);
                }
            });
        };

        // alle zuklappen
        field.$rows().each(function () {
            field.collapse($(this));
        });

        // eine geöffnet → die anderen schließen
        acf.addAction('show', function ($el, context) {
            if (context === 'collapse' && ownRow($el)) {
                closeOthers($el);
            }
        });

        // neue Zeile ist offen → die anderen schließen
        acf.addAction('append', function ($el) {
            if (ownRow($el)) {
                closeOthers($el);
            }
        });

        // Klick auf die zugeklappte Zeile öffnet sie (nicht auf die Schrift-Auswahl selbst)
        field.$el.on('click', '.acf-row.-collapsed > .acf-fields', function (e) {
            var $row = $(this).closest('.acf-row');
            if (!ownRow($row) || $(e.target).closest('select, input, a').length) {
                return;
            }
            field.expand($row);
        });
    });

    // ------------------------------------------------------------- Box „Schriftgrößen“
    // Vorlage lädt ihre Stufen in die Tabelle, Änderungen stellen die Vorlage auf „Eigene“.
    // „Fließtext“ wirkt wie ein Radio: es ist immer nur eine Stufe angehakt. Daten: zptTypePresets.
    var TYPE_PRESET = '.acf-field[data-name="zpt_type_preset"] select';
    var zptTypeFilling = false;

    var zptTypeMark = function () {
        $('.zpt-type-scale .acf-row').not('.acf-clone').each(function () {
            $(this).toggleClass('-base', $(this).find('[data-name="base"] input[type=checkbox]').prop('checked'));
        });
    };

    var zptTypeLoad = function (preset) {
        var field = acf.getField('field_zpt_type_scale');
        if (!field || typeof zptTypePresets === 'undefined' || !zptTypePresets[preset]) {
            return;
        }
        zptTypeFilling = true;
        field.$rows().remove();
        zptTypePresets[preset].steps.forEach(function (step) {
            field.add();
            var $row = field.$rows().last();
            $row.find('[data-name="name"] input').val(step[0]);
            $row.find('[data-name="size"] input').val(step[1]);
            $row.find('[data-name="line_height"] input').val(step[2]);
            $row.find('[data-name="base"] input[type=checkbox]').prop('checked', step[0] === zptTypePresets[preset].base);
        });
        field.render();
        zptTypeFilling = false;
        zptTypeMark();
    };

    $(document).on('change', TYPE_PRESET, function () {
        zptTypeLoad(this.value);
    });

    $(document).on('change', '.zpt-type-scale [data-name="base"] input[type=checkbox]', function () {
        if (this.checked) {
            $('.zpt-type-scale [data-name="base"] input[type=checkbox]').not(this).prop('checked', false);
        }
        zptTypeMark();
    });

    $(document).on('input change', '.zpt-type-scale input', function () {
        if (!zptTypeFilling) {
            $(TYPE_PRESET).val('custom');
        }
    });
    acf.addAction('append', function ($el) {
        if (!zptTypeFilling && $el.closest('.zpt-type-scale').length) {
            $(TYPE_PRESET).val('custom');
        }
    });

    // noch nie gespeichert (nur leere Zeilen) → Vorlage laden
    acf.addAction('ready', function () {
        var field = acf.getField('field_zpt_type_scale');
        if (field && field.$el.length) {
            var filled = field.$rows().find('[data-name="name"] input').filter(function () {
                return this.value !== '';
            }).length;
            if (!filled) {
                zptTypeLoad($(TYPE_PRESET).val() || '12punkt');
            }
        }
        zptTypeMark();
    });
})(jQuery);
