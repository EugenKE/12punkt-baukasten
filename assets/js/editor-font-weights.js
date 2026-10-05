/**
 * Block-Editor, Bereich „Darstellung“: unter „Gewicht“ nur die Gewichte der Schrift der gewählten
 * Schriftrolle anbieten.
 * Daten: zptFontWeights = {'': [Gewichte der Grundschrift] | null, 'serif': ['400', '600'], …}
 * (null = keine Einschränkung). Siehe includes/typography.php.
 */
(function ($) {
    if (typeof zptFontWeights === 'undefined') {
        return;
    }

    var FONT = '.acf-field[data-name="zpt_font_role"] select';
    var WEIGHT = '.acf-field[data-name="zpt_font_weight_class"] select';

    function zptAllowedWeights(role) {
        return Object.prototype.hasOwnProperty.call(zptFontWeights, role) ? zptFontWeights[role] : zptFontWeights[''];
    }

    /**
     * Optionen filtern. resetInvalid: Auswahl auf „Standard“ setzen, wenn sie in der Schrift fehlt
     * (nur bei Schriftwechsel, nicht beim Laden – sonst würde der Block ungefragt geändert).
     */
    function zptFilterWeights($fontSelect, resetInvalid) {
        var $weight = $fontSelect.closest('.acf-fields').find(WEIGHT).first();
        if (!$weight.length) {
            return;
        }
        var allowed = zptAllowedWeights($fontSelect.val());
        var current = $weight.val();

        $weight.find('option').each(function () {
            var value = this.value;
            var ok = !value || !allowed || allowed.indexOf(value.replace('fw-', '')) !== -1;
            var keep = ok || (!resetInvalid && value === current);
            this.hidden = !keep;
            this.disabled = !keep;
        });

        if (resetInvalid && current && $weight.find('option:selected').prop('disabled')) {
            $weight.val('').trigger('change');
        }
    }

    // Felder erscheinen in der Block-Seitenleiste erst nach dem Laden → beim Rendern prüfen
    function zptInit($el) {
        $el.find(FONT).addBack(FONT).each(function () {
            zptFilterWeights($(this), false);
        });
    }

    if (typeof acf !== 'undefined') {
        acf.addAction('ready', zptInit);
        acf.addAction('append', zptInit);
        acf.addAction('render_block_preview', function () {
            zptInit($(document));
        });
    }

    $(document).on('change', FONT, function () {
        zptFilterWeights($(this), true);
    });

    $(document).on('focus mousedown', WEIGHT, function () {
        var $font = $(this).closest('.acf-fields').find(FONT).first();
        if ($font.length) {
            zptFilterWeights($font, false);
        }
    });
})(jQuery);
