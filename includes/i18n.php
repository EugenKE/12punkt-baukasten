<?php
/**
 * Übersetzungen (Admin-Sprachen EN / CZ). Quellsprache der Texte ist Deutsch.
 *
 * - PHP: __('…', '12punkt-baukasten'), Dateien in languages/ (.po → .mo)
 * - ACF-Feldgruppen (acf-json): ACF übersetzt Beschriftungen, Anleitungen, Auswahlen usw.
 *   über die Textdomain (l10n_textdomain); Platzhalter/Prepend/Append ergänzt der Filter unten
 * - block.json: Titel/Beschreibung/Stichwörter übersetzt WordPress über "textdomain"
 * - JS: wp.i18n.__(), Übersetzungen als JSON in languages/ (wp_set_script_translations)
 *
 * Neue Texte: `npm run i18n:pot` → .po-Dateien ergänzen → `npm run i18n:build` (siehe bin/build-i18n.php).
 */

defined('ABSPATH') || exit;

const ZPT_TEXTDOMAIN = '12punkt-baukasten';

// Skripte mit übersetzbaren Texten (Handles)
const ZPT_I18N_SCRIPTS = ['zpt-admin-buttons', 'zpt-admin-colors', 'zpt-admin-elements', 'zpt-editor-title'];

add_action('init', function () {
    load_plugin_textdomain(ZPT_TEXTDOMAIN, false, dirname(plugin_basename(ZPT_PATH . '12punkt-baukasten.php')) . '/languages');
}, 0);

// ACF: Feldgruppen über unsere Textdomain übersetzen – nur, wenn keine andere gesetzt ist.
// Fremde Feldgruppen ohne Übersetzung bleiben dabei unverändert. Im Feldgruppen-Editor nicht übersetzen,
// sonst landen beim Speichern übersetzte Texte im acf-json.
add_filter('acf/settings/l10n_textdomain', fn($domain) => $domain ?: (zpt_i18n_is_field_group_editor() ? '' : ZPT_TEXTDOMAIN));

function zpt_i18n_is_field_group_editor(): bool {
    if (!is_admin()) {
        return false;
    }
    $post_type = sanitize_key($_REQUEST['post_type'] ?? '');
    if (!$post_type && !empty($_REQUEST['post']) && is_numeric($_REQUEST['post'])) {
        $post_type = get_post_type((int) $_REQUEST['post']);
    }
    // Ajax des Editors (Feldeinstellungen, Speichern)
    if (!$post_type && wp_doing_ajax() && str_starts_with(sanitize_text_field(wp_unslash($_REQUEST['action'] ?? '')), 'acf/field_group/')) {
        return true;
    }
    return $post_type === 'acf-field-group';
}

// ACF übersetzt Platzhalter, Prepend und Append nicht selbst
add_filter('acf/translate_field', function ($field) {
    foreach (['placeholder', 'prepend', 'append'] as $key) {
        if (isset($field[$key]) && is_string($field[$key]) && $field[$key] !== '') {
            $field[$key] = acf_translate($field[$key]);
        }
    }
    return $field;
});

// JS-Übersetzungen – kurz vor der Ausgabe, damit egal ist, in welchem Hook das Skript eingebunden wurde
function zpt_i18n_scripts(): void {
    foreach (ZPT_I18N_SCRIPTS as $handle) {
        if (wp_script_is($handle, 'enqueued')) {
            wp_set_script_translations($handle, ZPT_TEXTDOMAIN, ZPT_PATH . 'languages');
        }
    }
}
add_action('admin_print_scripts', 'zpt_i18n_scripts', 1);
add_action('admin_print_footer_scripts', 'zpt_i18n_scripts', 1);
