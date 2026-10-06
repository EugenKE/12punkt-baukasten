/**
 * Baukasten → Buttons
 * - Template „btn default“ (Kürzel default) nicht löschbar: blendet Löschen/Duplizieren/Hinzufügen
 *   der Zeile aus und sperrt das Kürzel. Der Server prüft zusätzlich beim Speichern (includes/buttons.php).
 * - Templates und Varianten beim Laden eingeklappt (wie die Elemente), neu hinzugefügte bleiben offen
 */
(function ($) {
    if (typeof acf === 'undefined') {
        return;
    }
    var __ = wp.i18n.__;

    function zptLockDefaultTemplate() {
        $('.acf-field[data-key="field_zpt_btn_templates"] .acf-row:not(.acf-clone)').each(function () {
            var $row = $(this);
            var $slug = $row.find('.acf-field[data-key="field_zpt_btn_tpl_slug"] input[type="text"]').first();
            if ($.trim($slug.val()) === 'default') {
                $row.addClass('zpt-row-locked');
                $slug.prop('readonly', true).attr('title', __('btn default – Kürzel fest, Zeile nicht löschbar', '12punkt-baukasten'));
            }
        });
    }

    // ACF merkt sich eingeklappte Zeilen je Browser („this.collapsedRows“) – hier immer alle zu
    function zptCollapseRows() {
        ['field_zpt_btn_templates', 'field_zpt_btn_variants'].forEach(function (key) {
            $('.acf-field[data-key="' + key + '"] > .acf-input > .acf-repeater > table > tbody > .acf-row:not(.acf-clone)').addClass('-collapsed');
        });
    }

    acf.addAction('ready', zptCollapseRows);
    acf.addAction('ready', zptLockDefaultTemplate);
    acf.addAction('append', zptLockDefaultTemplate);
})(jQuery);
