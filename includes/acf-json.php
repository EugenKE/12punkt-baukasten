<?php
/**
 * Feldgruppen liegen als ACF Local JSON im Plugin (acf-json/).
 * Gruppen mit Key "group_zpt_*" werden beim Speichern im ACF-Backend
 * wieder ins Plugin geschrieben, alle anderen bleiben beim Theme.
 * Außerdem: Infos (instructions) der Baukasten-Felder unter dem Eingabefeld (unten).
 */

defined('ABSPATH') || exit;

add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = ZPT_PATH . 'acf-json';
    return $paths;
});

add_filter('acf/json/save_paths', function ($paths, $post) {
    if (!empty($post['key']) && str_starts_with($post['key'], 'group_zpt_')) {
        return [ZPT_PATH . 'acf-json'];
    }
    return $paths;
}, 10, 2);

// ----------------------------------------------------------------- INFOS NACH DEM FELD
// ACF setzt die Position der Infos (instructions) nur je Feldgruppe. Baukasten-Felder (field_zpt_*):
// Info unter dem Eingabefeld; Gruppen, Repeater, Flexible Content, Clones u. ä. behalten sie davor
// (unter dem Label, Feldgruppen-Einstellung „label“). Gilt für Einstellungsseiten und Blocks.

// Typen, deren Info vor dem Inhalt bleibt
const ZPT_INFO_BEFORE_TYPES = ['group', 'repeater', 'flexible_content', 'clone', 'message', 'tab', 'accordion'];

add_filter('acf/prepare_field', function ($field) {
    if (
        !$field
        || empty($field['instructions'])
        || !str_starts_with((string) ($field['key'] ?? ''), 'field_zpt_')
        || in_array($field['type'] ?? '', ZPT_INFO_BEFORE_TYPES, true)
        || str_contains($field['wrapper']['class'] ?? '', 'zpt-info-top')
    ) {
        return $field;
    }
    $field['zpt_info_after'] = $field['instructions'];
    $field['instructions']   = '';
    return $field;
}, 20);

// nach dem Eingabefeld (ACF rendert es auf Priorität 0)
add_action('acf/render_field', function ($field) {
    if (!empty($field['zpt_info_after'])) {
        printf('<p class="description zpt-info-after">%s</p>', acf_esc_html($field['zpt_info_after']));
    }
}, 10);

add_action('acf/input/admin_enqueue_scripts', function () {
    wp_add_inline_style('acf-input', '.acf-field .acf-input > p.description.zpt-info-after {margin: 4px 0 0;}');
});
