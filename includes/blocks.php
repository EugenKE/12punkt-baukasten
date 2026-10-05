<?php
/**
 * Registrierung der Blocks (jeder Ordner in blocks/ mit block.json).
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- REGISTER BLOCKS
add_action('init', function () {
    foreach (glob(ZPT_PATH . 'blocks/*/block.json') as $block_json) {
        register_block_type(dirname($block_json));
    }
});

// ----------------------------------------------------------------- NO INNERBLOCKS WRAPPER
// ACF umschließt InnerBlocks im Frontend sonst mit <div class="acf-innerblocks-container">,
// das bricht .row > .col.
add_filter('acf/blocks/wrap_frontend_innerblocks', function ($wrap, $name) {
    return str_starts_with($name, 'zpt/') ? false : $wrap;
}, 10, 2);

// ----------------------------------------------------------------- ERLAUBTE BLOCKS
// Standard: alle Blocks (Baukasten + WordPress). In einer Row nur Spalten (zpt/row/allowed_blocks),
// in einer Spalte nur Komponenten (zpt/column/allowed_blocks).
// Nur Baukasten-Blocks im Editor:  add_filter('zpt/allowed_blocks', fn() => zpt_block_names());
// Einzelne Blocks zusätzlich:      add_filter('zpt/allowed_blocks', fn() => [...zpt_block_names(), 'core/paragraph']);
add_filter('allowed_block_types_all', function ($allowed, $context) {
    if (empty($context->post)) {
        return $allowed;
    }
    return apply_filters('zpt/allowed_blocks', $allowed, $context);
}, 10, 2);

/**
 * Namen aller Baukasten-Blocks (zpt/*).
 */
function zpt_block_names(): array {
    return array_values(array_filter(
        array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered()),
        fn($name) => str_starts_with($name, 'zpt/')
    ));
}

// ----------------------------------------------------------------- BLOCK CATEGORY
add_filter('block_categories_all', function ($categories) {
    array_unshift($categories, [
        'slug'  => 'zpt',
        'title' => '12punkt Baukasten',
        'icon'  => null,
    ]);
    return $categories;
});

// ----------------------------------------------------------------- EDITOR ASSETS
// enqueue_block_assets (statt enqueue_block_editor_assets), damit das CSS auch im iframe-Editor landet
add_action('enqueue_block_assets', function () {
    if (!is_admin()) {
        return;
    }
    wp_enqueue_style('zpt-editor', ZPT_URL . 'assets/css/editor.css', [], filemtime(ZPT_PATH . 'assets/css/editor.css'));
    wp_add_inline_style('zpt-editor', zpt_editor_column_css());
});

// --- Seitenleiste des Block-Editors breiter (WP-Standard 280px, gilt erst ab 782px Fensterbreite).
// WP setzt die Breite per JS als Inline-Style (Ein-/Ausblend-Animation) → !important nötig.
// Breite ändern: add_filter('zpt/editor/sidebar_width', fn() => 400);
add_action('enqueue_block_editor_assets', function () {
    $width = (int) apply_filters('zpt/editor/sidebar_width', 360);
    if ($width <= 0) {
        return;
    }
    wp_register_style('zpt-editor-sidebar', false);
    wp_enqueue_style('zpt-editor-sidebar');
    wp_add_inline_style('zpt-editor-sidebar', '@media (min-width: 782px) {.interface-complementary-area, .interface-complementary-area__fill {width: ' . $width . 'px !important;}}');
});

/**
 * Editor: Breite des Block-Wrappers aus den Bootstrap-Spaltenklassen der .zpt-col darin
 * (z. B. .wp-block:has(.zpt-col.col-md-6) → 50 % ab MD). Siehe assets/css/editor.css.
 */
function zpt_editor_column_css(): string {
    $breakpoints = apply_filters('zpt/editor/breakpoints', ['' => 0] + zpt_breakpoint_min());

    $wrapper = '.editor-styles-wrapper .zpt-row .wp-block';
    $css     = '';

    foreach ($breakpoints as $bp => $min_width) {
        $prefix = $bp ? 'col-' . $bp : 'col';
        $rules  = $wrapper . ':has(.zpt-col.' . $prefix . '){flex:1 0 0%;width:auto;}';
        for ($i = 1; $i <= 12; $i++) {
            $rules .= $wrapper . ':has(.zpt-col.' . $prefix . '-' . $i . '){flex:0 0 auto;width:' . round($i / 12 * 100, 6) . '%;}';
        }
        $css .= $min_width ? '@media (min-width:' . $min_width . 'px){' . $rules . '}' : $rules;
    }

    return $css;
}

/**
 * Blocks, die in einer Row eingefügt werden dürfen (Standard: nur Spalten).
 * Erweiterbar über den Filter "zpt/row/allowed_blocks".
 */
function zpt_row_allowed_blocks(): array {
    return apply_filters('zpt/row/allowed_blocks', [
        'zpt/column',
    ]);
}

/**
 * Komponenten, die in einer Spalte eingefügt werden dürfen.
 * Erweiterbar über den Filter "zpt/column/allowed_blocks".
 */
function zpt_column_allowed_blocks(): array {
    return apply_filters('zpt/column/allowed_blocks', [
        'zpt/headline',
        'zpt/text',
        'zpt/image',
        'zpt/button',
        'zpt/gallery',
        'zpt/card',
        'zpt/accordion',
        'zpt/icon',
        'zpt/video',
        'zpt/posts',
        'zpt/navigation',
        'zpt/anchors',
    ]);
}
