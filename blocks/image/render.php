<?php
/**
 * Block: zpt/image
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

$image   = get_field('image');
// Felder aus „ZPT Template: Bild-Einstellungen“, als Einzel-Klone auf die Bereiche Inhalt und Bildunterschrift verteilt
$options = [];
foreach (['add_to_photoswipe', 'css_figure', 'css_image_container', 'css_image', 'image_caption_source', 'caption_overwrite_local', 'css_image_caption'] as $name) {
    $value = get_field($name);
    if ($value !== null) {
        $options[$name] = $value;
    }
}
$photoswipe = (bool) ($options['add_to_photoswipe'] ?? true);

if ($photoswipe) {
    zpt_enqueue('photoswipe');
}

zpt_component('image', [
    'image_id'          => (int) ($image['ID'] ?? 0),
    'photoswipe'        => $photoswipe,
    'figure_classes'    => $options['css_figure'] ?? '',
    'container_classes' => $options['css_image_container'] ?? '',
    'img_classes'       => $options['css_image'] ?? '',
    'caption_source'    => $options['image_caption_source'] ?? 'caption_none',
    'caption_local'     => $options['caption_overwrite_local'] ?? '',
    'caption_classes'   => $options['css_image_caption'] ?? '',
    'message'           => zpt_message_html(get_field('message'), (string) get_field('css_message')),
    'attrs'             => zpt_block_attrs($block, ['zpt-image']),
    'is_preview'        => $is_preview,
]);
