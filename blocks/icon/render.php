<?php
/**
 * Block: zpt/icon
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

$style = [];
if ($size = zpt_css_value(get_field('size'))) {
    $style['font-size'] = $size;
}

zpt_component('icon', [
    'source'     => get_field('source') === 'svg' ? 'svg' : 'set',
    'icon'       => (string) get_field('icon'),
    'svg'        => zpt_sanitize_svg((string) get_field('svg')),
    'link'       => is_array(get_field('link')) ? get_field('link') : [],
    'label'      => (string) get_field('label'),
    'attrs'      => zpt_block_attrs($block, ['zpt-icon', get_field('css_classes')], $style),
    'is_preview' => $is_preview,
]);
