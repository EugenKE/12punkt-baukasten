<?php
/**
 * Block-Editor: Seitentitel nicht im Inhaltsbereich anzeigen.
 *
 * Der Titel wird über dem Inhalt ausgeblendet (die Seite zeigt ihn im Frontend ja auch nicht,
 * die Rows bringen ihre Überschriften selbst mit) und stattdessen in der Seitenleiste
 * (Tab „Seite“/„Beitrag“, oben bei Status/Template) bearbeitet.
 *
 * Für welche Inhaltstypen: add_filter('zpt/editor/hide_title', fn($types) => [...]);
 */

defined('ABSPATH') || exit;

/**
 * Wird der Titel beim aktuell bearbeiteten Inhaltstyp ausgeblendet?
 */
function zpt_editor_hides_title(): bool {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !$screen->is_block_editor()) {
        return false;
    }
    return in_array($screen->post_type, (array) apply_filters('zpt/editor/hide_title', ['page', 'post', 'section']), true);
}

// --- Titel im Inhaltsbereich ausblenden (enqueue_block_assets landet auch im iframe des Editors)
add_action('enqueue_block_assets', function () {
    if (!is_admin() || !zpt_editor_hides_title()) {
        return;
    }
    wp_register_style('zpt-editor-title', false);
    wp_enqueue_style('zpt-editor-title');
    wp_add_inline_style('zpt-editor-title', '.editor-visual-editor__post-title-wrapper, .edit-post-visual-editor__post-title-wrapper {display: none !important;}');
});

// --- Titel-Feld in der Seitenleiste
add_action('enqueue_block_editor_assets', function () {
    if (!zpt_editor_hides_title()) {
        return;
    }
    wp_enqueue_script(
        'zpt-editor-title',
        ZPT_URL . 'assets/js/editor-title.js',
        ['wp-plugins', 'wp-editor', 'wp-components', 'wp-data', 'wp-element'],
        filemtime(ZPT_PATH . 'assets/js/editor-title.js'),
        true
    );
    // Feld über die volle Breite der Übersicht
    wp_register_style('zpt-editor-title-field', false);
    wp_enqueue_style('zpt-editor-title-field');
    wp_add_inline_style('zpt-editor-title-field', '.zpt-title-field > *, .zpt-title-field .components-base-control {width: 100%; flex: 1 1 100%;}');
});
