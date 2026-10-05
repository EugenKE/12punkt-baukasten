<?php
/**
 * Block: zpt/headline
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

zpt_component('headline', [
    'text'  => get_field('headline') ?: get_the_title($post_id),
    'tag'   => get_field('headline_tag') ?: 'h2',
    'attrs' => zpt_block_attrs($block, ['section-headline', get_field('css_classes')]),
]);
