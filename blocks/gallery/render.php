<?php
/**
 * Block: zpt/gallery
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

$layout     = get_field('layout') ?: 'tiles';
$photoswipe = (bool) get_field('photoswipe');

if ($photoswipe) {
    zpt_enqueue('photoswipe');
}
if ($layout === 'masonry') {
    zpt_enqueue('masonry');
}

zpt_component('gallery', [
    'id'             => 'zpt-gallery-' . substr(md5($block['id'] ?? uniqid()), 0, 8),
    'images'         => array_map('intval', (array) (get_field('images') ?: [])),
    'layout'         => $layout,
    'grid_classes'   => zpt_per_row_classes(get_field('per_row'), get_field('gap')),
    'item_classes'   => get_field('css_item') ?: '',
    'controls'       => (bool) get_field('controls'),
    'indicators'     => (bool) get_field('indicators'),
    'interval'       => (float) get_field('interval'),
    'fade'           => (bool) get_field('fade'),
    'photoswipe'     => $photoswipe,
    'caption_source' => get_field('caption_source') ?: 'caption_none',
    'image_size'     => get_field('image_size') ?: 'large',
    'ratio'          => (string) get_field('ratio'),
    'message'        => zpt_message_html(get_field('message'), (string) get_field('css_message')),
    'attrs'          => zpt_block_attrs($block, ['zpt-gallery', 'zpt-gallery-' . $layout, get_field('css_classes')]),
    'is_preview'     => $is_preview,
]);
